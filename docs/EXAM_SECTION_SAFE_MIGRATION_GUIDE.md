# Guide: Section-Safe Exam Migration (Shared Exam per Grade)

Use this guide when implementing the same exam/section change in a **localized sibling project** with the same overall structure (Laravel + Livewire admin/student exams).

This guide reflects the **production-safe hybrid approach** — not a hard cutover.

---

## Goal

- **Future exams:** scoped by **school + grade + term (round)** only — **no section**, no level.
- **Legacy exams:** keep working if they still have `section_id` set (old section-specific records).
- **Students:** still have `section_id` (profile, division, reports). Do **not** remove student section.
- **Answers / attempts:** do **not** change `take_exams`, result tables, or submit controllers for section logic.

**Terminology:** Round = Term (DB column is usually `term`).

---

## Two exam types in the system (during transition)

| Type | `exams.section_id` | Who uses it |
|------|-------------------|-------------|
| **Legacy section exam** | Set (matches a section) | Students in that section only |
| **New grade-wide exam** | `NULL` | Students whose section has no dedicated legacy exam |

**Resolution rule (student fetch + sync):**

1. Find candidate exams: same **school + grade + term + status** (no level in this project).
2. If student’s section has a legacy exam (`section_id = student.section_id`) → use that.
3. Else use grade-wide exam (`section_id IS NULL`).
4. If neither exists → student has no exam for that term.

---

## Skill / activity types in the sibling project

This project does **NOT** use exam/student **levels**.

Exam skill columns / activity types are:

- `reading`
- `listening_speaking` (may be one combined type — use exact column names from that DB)
- `writing`
- `ssv`

When adapting bulk create, add exam, activity assignment, and bulk refresh logic, use **that project’s type list** — not reading/listening/writing/speaking/sentences_structures from the source AST project.

---

## Step 1 — Database migration (safe)

Create migration:

1. Alter `exams.section_id` to **nullable**.
2. **Do NOT** run `UPDATE exams SET section_id = NULL` on production.
   - Legacy section exams must keep their `section_id` for fallback matching.
3. Do **not** drop the column.

```php
Schema::table('exams', function (Blueprint $table) {
    $table->foreignId('section_id')->nullable()->change();
});

// Keep existing section_id values for legacy section-specific exams.
// New grade-wide exams are created with section_id = null.
```

---

## Step 2 — Add centralized logic on `Exam` model

Add these methods to `app/Models/Exam.php`. **Remove all level parameters and level SQL** — this project has no levels.

**Required imports at top of `Exam.php` (use Support Collection, NOT Eloquent Collection):**

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection; // NOT Illuminate\Database\Eloquent\Collection
```

> **Common error:** `pluck()` and `collect()` return `Illuminate\Support\Collection`.
> If you type-hint `Illuminate\Database\Eloquent\Collection` on `sectionIdsWithDedicatedExam()`, PHP will throw:
> *Return value must be of type Illuminate\Database\Eloquent\Collection, Illuminate\Support\Collection returned*

### 2.1 Uniqueness (create / edit guard)

Blocks creating a **new** exam when **any** exam already exists for the grade+term (legacy section OR grade-wide).

```php
/**
 * True if any exam exists for school + grade + term (any section_id).
 */
public static function existsForGradeTerm(
    int $schoolId,
    int $gradeId,
    string $term,
    ?int $excludeExamId = null
): bool {
    return static::query()
        ->where('school_id', $schoolId)
        ->where('grade_id', $gradeId)
        ->where('term', $term)
        ->when($excludeExamId, fn ($q) => $q->where('id', '!=', $excludeExamId))
        ->exists();
}
```

**Rules:**
- **Add exam / bulk create:** call before insert; throw or skip if true.
- **Edit exam:** call only when **placement changes** (school, grade, or term), with `$excludeExamId = current exam id`. Do not block editing times/activities on an existing record.

---

### 2.2 Student exam resolution (fetch)

```php
/**
 * Candidates for a student before section/grade-wide pick.
 */
public static function candidateQueryForStudent(Student $student, array $statuses): Builder
{
    return static::query()
        ->where('school_id', $student->school_id)
        ->where('grade_id', $student->grade_id)
        ->whereIn('status', $statuses)
        ->orderByDesc('created_at');
}

/**
 * Pick one exam from candidates: section-specific first, else grade-wide (null section).
 */
public static function pickResolvedFromCandidates(\Illuminate\Support\Collection $candidates, Student $student): ?self
{
    if ($candidates->isEmpty()) {
        return null;
    }

    if ($student->section_id) {
        $sectionExam = $candidates->firstWhere('section_id', $student->section_id);
        if ($sectionExam) {
            return $sectionExam;
        }
    }

    return $candidates->firstWhere('section_id', null);
}

/**
 * One resolved exam per term for a student.
 */
public static function resolvedExamsForStudent(Student $student, array $statuses): \Illuminate\Support\Collection
{
    return static::candidateQueryForStudent($student, $statuses)
        ->get()
        ->groupBy('term')
        ->map(fn (\Illuminate\Support\Collection $group) => static::pickResolvedFromCandidates($group, $student))
        ->filter()
        ->values();
}

/**
 * Latest resolved exam (by created_at) — for single skill attempt entry.
 */
public static function resolveLatestForStudent(Student $student, array $statuses): ?self
{
    return static::resolvedExamsForStudent($student, $statuses)
        ->sortByDesc('created_at')
        ->first();
}

/**
 * Resolved exam for one term (optional helper).
 */
public static function resolveForStudent(Student $student, array $statuses, ?string $term = null): ?self
{
    if ($term !== null) {
        $candidates = static::candidateQueryForStudent($student, $statuses)
            ->where('term', $term)
            ->get();

        return static::pickResolvedFromCandidates($candidates, $student);
    }

    return static::resolveLatestForStudent($student, $statuses);
}
```

---

### 2.3 Sync: who gets linked to which exam

```php
/**
 * Section IDs that already have a dedicated legacy exam for this school+grade+term.
 */
protected function sectionIdsWithDedicatedExam(): \Illuminate\Support\Collection
{
    if (empty($this->school_id) || empty($this->grade_id) || empty($this->term)) {
        return collect();
    }

    return static::query()
        ->where('school_id', $this->school_id)
        ->where('grade_id', $this->grade_id)
        ->where('term', $this->term)
        ->whereNotNull('section_id')
        ->when($this->id, fn ($q) => $q->where('id', '!=', $this->id))
        ->pluck('section_id'); // returns Support\Collection
}

/**
 * Students who should be linked to THIS exam when syncing.
 */
public function eligibleStudentsQuery(): Builder
{
    $query = Student::query()
        ->where('school_id', $this->school_id)
        ->where('grade_id', $this->grade_id);

    if ($this->section_id) {
        // Legacy section exam → only that section
        $query->where('section_id', $this->section_id);
    } else {
        // Grade-wide exam → students whose section has NO dedicated legacy exam for this term
        $sectionsWithOwnExam = $this->sectionIdsWithDedicatedExam();

        if ($sectionsWithOwnExam->isNotEmpty()) {
            $query->where(function (Builder $q) use ($sectionsWithOwnExam) {
                $q->whereNull('section_id')
                    ->orWhereNotIn('section_id', $sectionsWithOwnExam);
            });
        }
    }

    return $query;
}

/**
 * Insert missing student_exams rows for eligible students only.
 */
public function syncStudentExams(): int
{
    if (empty($this->school_id) || empty($this->grade_id)) {
        return 0;
    }

    $studentIds = $this->eligibleStudentsQuery()->pluck('id');

    if ($studentIds->isEmpty()) {
        return 0;
    }

    $existingStudentIds = StudentExam::query()
        ->where('exam_id', $this->id)
        ->whereIn('student_id', $studentIds)
        ->pluck('student_id');

    $missing = $studentIds->diff($existingStudentIds);

    if ($missing->isEmpty()) {
        return 0;
    }

    StudentExam::insert(
        $missing->map(fn ($studentId) => [
            'exam_id' => $this->id,
            'student_id' => $studentId,
        ])->values()->all()
    );

    return $missing->count();
}
```

**Note:** Source AST project passes `$filterByStudentLevel` because it has levels. **Omit that parameter entirely** in the sibling project.

---

## Step 3 — Where to use resolution vs sync (only where it matters)

Do **not** rewrite unrelated code. Change only exam **fetch**, **assign**, and **duplicate checks**.

### Admin — create / edit / bulk

| Action | Rule |
|--------|------|
| **Add exam** | Set `section_id = null`. Check `existsForGradeTerm()` before create. Call `syncStudentExams()` after create. |
| **Create for all grades** | Loop **grades**, not sections. Skip grade if `existsForGradeTerm()`. |
| **Edit exam** | **Preserve** existing `section_id` on save (do not force null). Uniqueness only if school/grade/term changed. Call `syncStudentExams()` + remove orphan links via `eligibleStudentsQuery()`. |
| **Bulk create** | Preview/create per **grade**. Reusable exam: prefer `section_id IS NULL` then any pending/active match. Block create if `existsForGradeTerm()`. New rows: `section_id = null`. |

Activity assignment loops must use **that project’s types**: `reading`, `listening_speaking`, `writing`, `ssv` (verify exact attribute/column names).

### Admin — list / resync

- Remove section filter from exam list (if present).
- **Resync students:** foreach exam → `$exam->syncStudentExams()` (uses hybrid eligible rules).

### Admin — exam check / exports

- Auto-sync missing rows: use `$exam->eligibleStudentsQuery()` per exam (not “all students in grade” blindly).
- Display **student section** (`Student->Section`), not `Exam->Section`.
- Export/search section column = student section.

### Admin — add / edit student

- Link exams via `Exam::resolvedExamsForStudent($student, ['active', 'pending'])`.
- On edit: if only **section** changes (same school/grade), **do not** delete exam links.
- On school/grade change: delete old placement links, then resolve again.

### Admin — grades/sections merge

- Stop updating `exams.section_id` when merging sections (exams no longer tied to section for new logic).

### Student — exam list + all skill attempt components

Apply to:

- Main student exam dashboard/list component
- **Every** per-type attempt component (all skill types in that project)

**Do not** filter `Exam` by `section_id = student.section_id` directly.

**Pattern for skill attempts:**

```php
$student = auth()->user();
$exam = Exam::resolveLatestForStudent($student, ['active']);

if (! $exam) {
    return redirect()->route('student.exams')->with(['error' => 'Student Exam Not Exist']);
}

$studentExam = StudentExam::where('student_id', $student->id)
    ->where('exam_id', $exam->id)
    ->where('checked', false)
    ->first();
```

**Pattern for exam list / firstOrCreate:**

```php
$activeExams = Exam::resolvedExamsForStudent($student, ['active']);
foreach ($activeExams as $exam) {
    StudentExam::firstOrCreate([
        'student_id' => $student->id,
        'exam_id' => $exam->id,
    ]);
}
```

### Controllers (submit answers)

- **No change** expected for exam save/submit controllers — they use `student_exam_id`.
- Question bank controller: only change if it incorrectly filters exams by section (rare).

---

## Step 4 — UI / views

- Remove section dropdown from add/edit exam forms.
- Remove section column/filter from admin exam list (optional: show Term).
- Expired exam cards: show **Term**, not `Exam->Section`.
- Results/check views: label **Student Section** from student record.
- Student header can still show division from `auth()->user()->Section`.

---

## Step 5 — What NOT to change

- `take_exams` and answer storage
- Result/marks tables and scoring
- Student `section_id` field
- Drop `exams.section_id` column
- Mass-null all exam `section_id` on production
- Hard-delete duplicate legacy exams on deploy day

---

## Step 6 — Verification checklist (agent must run)

### Grep (must find zero bad usages)

Search for exam assignment/fetch still using:

- `->where('section_id', $student->section_id)` on **Exam** queries
- `Exam->Section` / `Exam.Section` in blades
- `whereHas('Exam', ... section_id ...)` in student components

**Allowed** `section_id` usages:

- `students.section_id`
- Section CRUD / student forms
- `Exam` model internals (`pickResolvedFromCandidates`, `eligibleStudentsQuery`)
- New creates explicitly setting `'section_id' => null`

### Behavioral tests

1. Legacy section exam exists → only that section’s students resolve to it.
2. Grade-wide exam (`section_id` null) → other sections use it when no legacy exam.
3. Create new exam for grade+term when legacy exists → **blocked**.
4. Edit legacy exam times only → allowed; `section_id` preserved.
5. Resync → each student linked to **one** resolved exam per term, not all duplicates.
6. All skill types still attempt and submit answers.

---

## Step 7 — Production deploy (safe sequence)

1. Deploy migration (nullable only — **keep** legacy `section_id` values).
2. Deploy code with hybrid resolution + uniqueness.
3. Create **new** exams with `section_id = null` (grade-wide) for new terms.
4. Run admin **Resync Students** once.
5. Over time: expire old legacy section exams when no longer needed.
6. **Do not** mass-delete duplicate exams unless attempts are migrated or confirmed empty.

---

## Quick reference: old vs new behavior

| Topic | Old | New |
|-------|-----|-----|
| New exam scope | school + grade + section + term | school + grade + term (`section_id = null`) |
| Legacy exams | section-specific | still work via resolution priority |
| Student fetch | match exam.section_id | `resolveLatestForStudent` / `resolvedExamsForStudent` |
| Sync | section-only students | `eligibleStudentsQuery()` hybrid rules |
| Uniqueness | per section | per grade+term (any section_id) |
| Levels | N/A in sibling project | **omit all level logic** |
| Skill types | project-specific | reading, listening_speaking, writing, ssv |

---

## Agent instructions (summary)

1. Add nullable migration **without** mass-null update.
2. Add `Exam` model helpers above (**no level** variants).
3. Wire resolution into student fetch + all skill attempt components.
4. Wire sync/uniqueness into admin create/edit/bulk/resync/student link.
5. Show student section from **student**, not exam.
6. Leave answer/submit pipelines untouched.
7. Grep verify + test hybrid scenarios before marking done.

If any student or admin path still filters `Exam` by `section_id` directly (outside `Exam` model helpers), the implementation is **incomplete**.
