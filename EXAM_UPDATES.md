# Legacy Section Exams + Grade-Wide Exams — Resolution Fix Reference

Use this document in the **other project** to check whether the same bugs exist and to apply the same fixes.

**Scope:** Only the recent fix for coexisting **legacy section exams** (`section_id` set) and **new grade-wide exams** (`section_id = null`). 
---

## Problem that existed

1. **Section-first resolution** — If a student had Section A, any old Section A exam was returned even when a **newer grade-wide exam** existed for the same term.
2. **Wrong exam for unassigned sections** — For example Section C student could inherit Section A or B exam logic incorrectly in some flows.
3. **Pending exams invisible** — New exams are created as `pending`, but student dashboard only loaded `active`, so nothing appeared after creating a grade-wide exam.
4. **Stale `student_exams` rows** — Old section assignments were not cleaned when resolution changed.
5. **Duplicate check too broad** — `existsForGradeTerm()` blocked creating a grade-wide exam when legacy section exams existed for the same term.
6. **Grade-wide sync excluded sections** — `eligibleStudentsQuery()` skipped students in sections that had any legacy section exam.

---

## Ideal use case (target behaviour)

### What applies to a student (one term / round)

An exam is **applicable** to a student if:

| Exam `section_id` | Applies to |
|-------------------|--------------|
| `NULL` | Every student in that **school + grade** |
| Set (e.g. Section A) | Only students whose `section_id` matches |

### Which exam wins (same term)

Among **applicable** exams for that term, pick the one with the **latest `created_at`**.

Examples:

| Student | Exams (same term) | Result |
|---------|-------------------|--------|
| Section A | Section A (Jan), grade-wide (Mar) | **Grade-wide** (newer) |
| Section A | Section A only | Section A |
| Section C | Section A + Section B only | **No exam** |
| Section C | Grade-wide only | Grade-wide |
| Any | Grade-wide only | Grade-wide |

### Status rules

| Context | Statuses used |
|---------|---------------|
| Student **dashboard** (show skills, exam details) | `active` + `pending` |
| Student **attempt** (open skill exam) | `active` only |
| Admin sync on create | All eligible students get `student_exams` row; student sees pending until admin sets **active** |

### Latest exam on student dashboard

1. Resolve all applicable exams per term → list of exam IDs.
2. Ensure `student_exams` rows exist for those IDs (`firstOrCreate`).
3. Delete **unchecked** `student_exams` for same school/grade that are **not** in resolved list (legacy cleanup).
4. Pick **one** row: unchecked, resolved exam ID, highest `exam.created_at`.

If nothing resolves → show “no exam available”.

---

## Files to check in the other project

| File | What to verify |
|------|----------------|
| `app/Models/Exam.php` | Resolution helpers + sync query |
| `app/Livewire/Student/Exams/SkillExams.php` | Student dashboard mount logic |
| `app/Models/StudentExam.php` | `resolveForActiveAttempt()` uses `active` only |
| `app/Livewire/Admin/Exams/AddExam.php` | `section_id = null` on create; `existsForGradeTerm` |
| `resources/views/livewire/student/exams/skill-exams.blade.php` | Optional: exam details panel + empty state |

---

## Fix 1 — `Exam::pickResolvedFromCandidates()` 

**Bad (old):** Section match first, then grade-wide — ignores date.

```php
// OLD — do NOT use
if ($student->section_id) {
    $sectionExam = $candidates->firstWhere('section_id', $student->section_id);
    if ($sectionExam) return $sectionExam;
}
return $candidates->firstWhere('section_id', null);
```

**Good (current):** Filter applicable, then latest `created_at`.

```php
public static function pickResolvedFromCandidates(Collection $candidates, Student $student): ?self
{
    return $candidates
        ->filter(function (self $exam) use ($student) {
            if ($exam->section_id === null) {
                return true;
            }
            return $student->section_id
                && (int) $exam->section_id === (int) $student->section_id;
        })
        ->sortByDesc('created_at')
        ->first();
}
```

**How to test in other project:** Search for `pickResolvedFromCandidates` or `firstWhere('section_id', $student->section_id)`. If section is checked before sorting by date, the bug exists.

---

## Fix 2 — `Exam::existsForGradeTerm()`

Only prevent duplicate **grade-wide** exams. Legacy section rows must not block new grade exam.

```php
->whereNull('section_id')  // add this line
```

**Test:** Can admin create a grade-wide exam for Term 1 when Section A/B already have section exams for Term 1? Should be **yes**.

---

## Fix 3 — `Exam::eligibleStudentsQuery()`

**Bad (old):** Grade-wide exam excluded students in sections that had any dedicated section exam.

**Good (current):** Grade-wide sync = all students in school + grade. Section exam sync = that section only.

```php
public function eligibleStudentsQuery(): Builder
{
    $query = Student::query()
        ->where('school_id', $this->school_id)
        ->where('grade_id', $this->grade_id);

    if ($this->section_id) {
        $query->where('section_id', $this->section_id);
    }

    return $query;
}
```

Remove any `sectionIdsWithDedicatedExam()` / `whereNotIn('section_id', ...)` logic on grade-wide path.

---

## Fix 4 — Constant for dashboard visibility

```php
public const STUDENT_VISIBLE_STATUSES = ['active', 'pending'];
```

Use in `SkillExams` (and anywhere student **sees** exam list). Keep `['active']` only for **attempt** paths (`StudentExam::resolveForActiveAttempt`, etc.).

---

## Fix 5 — `SkillExams::mount()` 

**Checklist for other agent:**

```php
// 1. Resolve with active + pending (not active only)
$visibleExams = Exam::resolvedExamsForStudent($std, Exam::STUDENT_VISIBLE_STATUSES);

// 2. firstOrCreate student_exams for resolved exams
foreach ($visibleExams as $exam) { ... }

$resolvedExamIds = $visibleExams->pluck('id')->all();

// 3. ALWAYS clean stale unchecked rows (not only when resolvedExamIds non-empty)
StudentExam::where('student_id', $std->id)
    ->where('checked', false)
    ->whereHas('Exam', fn ($q) => $q
        ->whereIn('status', Exam::STUDENT_VISIBLE_STATUSES)
        ->where('school_id', $std->school_id)
        ->where('grade_id', $std->grade_id))
    ->when(!empty($resolvedExamIds), fn ($q) => $q->whereNotIn('exam_id', $resolvedExamIds))
    ->delete();

// 4. Latest = unchecked + resolved + max exam.created_at (not student_exam.created_at)
$this->latestExam = empty($resolvedExamIds) ? null : StudentExam::...
    ->whereIn('exam_id', $resolvedExamIds)
    ->where('checked', false)
    ->with(['Exam.Grade', 'Exam.Section'])
    ->get()
    ->sortByDesc(fn ($row) => $row->Exam?->created_at)
    ->first();
```

**Red flags in other project:**

- `resolvedExamsForStudent($std, ['active'])` only on dashboard → pending exams never show
- Cleanup wrapped in `if (!empty($resolvedExamIds))` only → pending rows deleted when nothing active
- `latestExam` query without `whereIn('exam_id', $resolvedExamIds)` when list empty → can show wrong legacy exam

---

## Fix 6 — `StudentExam::resolveForActiveAttempt()`

Should stay **active-only** (do not change to pending):

```php
$exam = Exam::resolveLatestForStudent($student, ['active']);
```

Used by `ReadingExam`, `ListeningExam`, `SpeakingExam`, etc.

---

## Quick diagnostic in other project

Run through these questions:

1. Does `pickResolvedFromCandidates` prefer section exam without comparing `created_at`? → **Fix needed**
2. Does student dashboard only query `active` exams? → **Fix needed** (add pending for display)
3. After creating pending grade-wide exam, does student see nothing? → **Fix 4 + 5**
4. Section C student sees Section A exam? → **Fix 1**
5. Cannot create grade-wide exam because section exams exist for same term? → **Fix 2**
6. Grade-wide exam created but Section A students not synced? → **Fix 3**

---

## Related helpers (should all use same resolution)

These should call `resolvedExamsForStudent` / `resolveForStudent` / `pickResolvedFromCandidates`:

- `SkillExams.php` (dashboard)
- `StudentExam::resolveForActiveAttempt()` (attempt)
- `AddStudent.php` / `EditStudent.php` (assign on enroll)
- `StudentsWithoutExam::refreshExams()`

Do **not** duplicate ad-hoc `where section_id = ...` logic elsewhere unless it matches the table above.

---

## Optional UI (already in this project)

`skill-exams.blade.php` — exam details block (grade, section vs grade scope, round, status) + empty state message. Not required for logic fix but helps testing.

Lang keys in `message.php`: `no_exam_available`, `exam_details`, `exam_scope`, `section_based_exam`, `grade_based_exam`, `pending`, `round`.

---

*Reference for porting the legacy/grade-wide exam resolution fix only.*
