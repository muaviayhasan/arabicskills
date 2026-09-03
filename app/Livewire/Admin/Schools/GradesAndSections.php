<?php

namespace App\Livewire\Admin\Schools;

use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GradesAndSections extends Component
{
    public $school_id;

    public $classes;

    public $new_section = [];

    public $edit_section = [];

    public $searchWord;

    public $grade_id;

    /** @var array<string, array<int, array<string, mixed>>> */
    public array $mergePreview = [];

    protected $queryString = [
        'searchWord' => ['except' => ''],
        'grade_id' => ['except' => ''],
    ];

    protected $listeners = [
        'sectionDelete', 'updateTabs',
    ];

    public function manageSearch()
    {
        // Search is reactive, no need for reset page
    }

    public function resetFilters()
    {
        $this->searchWord = '';
        $this->grade_id = '';
    }

    public function updateTabs($key, $valu)
    {
        $this->new_section[$key] = $valu;
    }

    public function mount($school_id)
    {
        $this->school_id = $school_id;

        $this->classes = Grade::query()->whereNotNull('number')->orderBy('number')->get();
    }

    public function addSection()
    {

        $this->validate([
            'new_section.names' => 'required|array|min:1',
            'new_section.grade_id' => 'required',
        ]);
        foreach ($this->new_section['names'] as $elm) {
            Section::create([
                'name' => $elm,
                'grade_id' => $this->new_section['grade_id'],
                'school_id' => $this->school_id,
            ]);
        }

        $this->dispatch(
            'swal:alert',
            title: 'New grade section added successfully.',
            icon: 'success',
            modal: '#addSection'
        );
    }

    public function editSection($sec)
    {
        $this->edit_section = $sec;
    }

    public function updateSection()
    {
        $this->validate([
            'edit_section.name' => 'required',
            'edit_section.grade_id' => 'required',
        ]);

        Section::find($this->edit_section['id'])->update($this->edit_section);
        $this->edit_section = [];
        $this->dispatch(
            'swal:alert',
            title: 'Section updated successfully.',
            icon: 'success',
            modal: '#editSection'

        );
    }

    public function deleteSection($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'Section may have students, all will be deleted permanently.',
            id: $id,
            emitBack: 'sectionDelete',
        );
    }

    public function SectionDelete($id)
    {
        $sch = Section::findOrFail($id);
        $sch->delete();
        $this->dispatch(
            'swal:toast',
            title: 'Section deleted successfully.',
            icon: 'success',
        );
    }

    public function previewMergeSections(): void
    {
        $preview = [
            'cross_grade' => [],
            'grade_consolidation' => [],
            'orphaned' => [],
            'same_grade' => [],
            'mismatched' => [],
        ];

        // ── Preview Step 1: Cross-grade duplicate sections ────────────────────────
        $rows = DB::select('
            SELECT sec.id, sec.name AS section_name, sec.grade_id,
                   g.name  AS grade_name,
                   g.number AS grade_number,
                   (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count
            FROM   sections sec
            JOIN   grades   g ON g.id = sec.grade_id
            WHERE  sec.school_id = ?
            ORDER  BY sec.id ASC
        ', [$this->school_id]);

        $crossGroups = collect($rows)->groupBy(function ($s) {
            $normName = preg_replace('/\s+/', '', strtolower($s->grade_name));
            $normLevel = (string) ($s->grade_number ?? '');
            $normSec = strtolower(trim($s->section_name));

            return "{$normName}__{$normLevel}__{$normSec}";
        });

        foreach ($crossGroups as $group) {
            if ($group->count() <= 1) {
                continue;
            }

            // Prefer the canonical numbered grade; fall back to lowest section ID.
            $sorted = $group->sortByDesc(
                fn ($s) => ((int) ($s->grade_number ?? 0)) * 1_000_000 - $s->id
            )->values();
            $keeper = $sorted->first();
            $duplicates = $sorted->slice(1);

            foreach ($duplicates as $dup) {
                $preview['cross_grade'][] = [
                    'action' => 'Merge into existing section',
                    'from_section' => "#{$dup->id} — {$dup->section_name}",
                    'from_grade' => $dup->grade_name,
                    'into_section' => "#{$keeper->id} — {$keeper->section_name}",
                    'into_grade' => $keeper->grade_name,
                    'students' => (int) $dup->student_count,
                ];
            }
        }

        // ── Preview Step 1b: Grade consolidation ──────────────────────────────────
        // Sections that live under a non-canonical (malformatted) grade record but
        // have no same-named section under the canonical equivalent grade.
        // Rather than merging/deleting, we simply reassign the section (and its
        // students) to the canonical grade record.
        // ─────────────────────────────────────────────────────────────────────────
        $allGrades = DB::select('SELECT id, name, number FROM grades WHERE number IS NOT NULL');

        $gradeGroups = collect($allGrades)->groupBy(function ($g) {
            $normName = preg_replace('/\s+/', '', strtolower($g->name));
            $normLevel = (string) ($g->number ?? '');

            return "{$normName}__{$normLevel}";
        });

        // Build a lookup of section IDs already captured by Step 1 (to skip duplicates)
        $alreadyMergedSectionIds = collect($preview['cross_grade'])
            ->map(fn ($r) => (int) ltrim(explode(' ', $r['from_section'])[0], '#'))
            ->flip();

        foreach ($gradeGroups as $gradeGroup) {
            if ($gradeGroup->count() <= 1) {
                continue;
            }

            $sortedGrades = $gradeGroup->sortByDesc(
                fn ($g) => ((int) ($g->number ?? 0)) * 1_000_000 - $g->id
            )->values();

            $canonicalGrade = $sortedGrades->first();
            $nonCanonicalGrades = $sortedGrades->slice(1);

            foreach ($nonCanonicalGrades as $ncGrade) {
                $ncSections = DB::select('
                    SELECT sec.id, sec.name AS section_name,
                           (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count
                    FROM   sections sec
                    WHERE  sec.school_id = ?
                      AND  sec.grade_id  = ?
                ', [$this->school_id, $ncGrade->id]);

                foreach ($ncSections as $sec) {
                    // Skip sections already handled by Step 1
                    if (isset($alreadyMergedSectionIds[$sec->id])) {
                        continue;
                    }

                    // Check if the canonical grade already has a section with the same name
                    $conflict = DB::selectOne('
                        SELECT id FROM sections
                        WHERE  school_id = ?
                          AND  grade_id  = ?
                          AND  LOWER(TRIM(name)) = LOWER(TRIM(?))
                    ', [$this->school_id, $canonicalGrade->id, $sec->section_name]);

                    if ($conflict) {
                        // Has a naming conflict — Step 1 should have caught it; skip
                        continue;
                    }

                    $preview['grade_consolidation'][] = [
                        'section' => "#{$sec->id} — {$sec->section_name}",
                        'from_grade' => "#{$ncGrade->id} {$ncGrade->name}",
                        'into_grade' => "#{$canonicalGrade->id} {$canonicalGrade->name}",
                        'students' => (int) $sec->student_count,
                    ];
                }
            }
        }

        // ── Preview Step 2: Orphaned sections ────────────────────────────────────
        $orphaned = DB::select('
            SELECT sec.id   AS section_id,
                   sec.name AS section_name,
                   sec.grade_id AS section_grade_id,
                   g.name  AS section_grade_name,
                   g.number AS section_grade_number,
                   (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count
            FROM   sections sec
            JOIN   grades   g ON g.id = sec.grade_id
            WHERE  sec.school_id = ?
              AND  (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) > 0
              AND  (SELECT COUNT(*) FROM students st
                    WHERE  st.section_id = sec.id
                      AND  st.grade_id   = sec.grade_id) = 0
        ', [$this->school_id]);

        foreach ($orphaned as $section) {
            $gradeRows = DB::select('
                SELECT grade_id, COUNT(*) AS cnt
                FROM   students
                WHERE  section_id = ?
                GROUP  BY grade_id
            ', [$section->section_id]);

            if (count($gradeRows) !== 1) {
                $preview['orphaned'][] = [
                    'action' => 'Skipped — students have mixed grades',
                    'section' => "#{$section->section_id} — {$section->section_name}",
                    'section_grade' => $section->section_grade_name,
                    'students' => (int) $section->student_count,
                    'target_grade' => 'Mixed',
                ];

                continue;
            }

            $targetGradeId = $gradeRows[0]->grade_id;
            $targetGrade = DB::selectOne('SELECT id, name, number FROM grades WHERE id = ?', [$targetGradeId]);

            $existing = DB::selectOne('
                SELECT id FROM sections
                WHERE  school_id = ?
                  AND  grade_id  = ?
                  AND  LOWER(TRIM(name)) = LOWER(TRIM(?))
                  AND  id != ?
                ORDER  BY id ASC
                LIMIT  1
            ', [$this->school_id, $targetGradeId, $section->section_name, $section->section_id]);

            $preview['orphaned'][] = [
                'action' => $existing
                    ? "Merge into section #{$existing->id}"
                    : "Update section grade_id to #{$targetGradeId}",
                'section' => "#{$section->section_id} — {$section->section_name}",
                'section_grade' => $section->section_grade_name,
                'target_grade' => $targetGrade
                    ? $targetGrade->name
                    : "Grade #{$targetGradeId}",
                'students' => (int) $section->student_count,
            ];
        }

        // ── Preview Step 3: Same-grade duplicate sections ─────────────────────────
        // Reload sections in case Step 1 would have deleted some (simulated)
        $sectionRows = DB::select('
            SELECT sec.id, sec.name, sec.grade_id,
                   g.name AS grade_name, g.number AS grade_number,
                   (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count
            FROM   sections sec
            JOIN   grades g ON g.id = sec.grade_id
            WHERE  sec.school_id = ?
            ORDER  BY sec.id ASC
        ', [$this->school_id]);

        $sameGradeGroups = collect($sectionRows)->groupBy(
            fn ($s) => $s->grade_id.'__'.mb_strtolower(trim($s->name))
        );

        foreach ($sameGradeGroups as $group) {
            if ($group->count() <= 1) {
                continue;
            }

            $keeper = $group->first();
            $duplicates = $group->slice(1);

            foreach ($duplicates as $dup) {
                $preview['same_grade'][] = [
                    'action' => "Merge into section #{$keeper->id}",
                    'from_section' => "#{$dup->id} — {$dup->name}",
                    'into_section' => "#{$keeper->id} — {$keeper->name}",
                    'grade' => $dup->grade_name,
                    'students' => (int) $dup->student_count,
                ];
            }
        }

        // ── Preview Step 4: Remaining mismatched students ─────────────────────────
        $mismatched = DB::select('
            SELECT s.id AS student_id, s.name AS student_name,
                   s.grade_id AS student_grade_id,
                   g_s.name   AS student_grade_name,
                   g_s.number  AS student_grade_number,
                   s.section_id,
                   sec.name   AS section_name,
                   sec.grade_id AS section_grade_id,
                   g_sec.name  AS section_grade_name,
                   g_sec.number AS section_grade_number
            FROM   students s
            JOIN   grades   g_s  ON g_s.id  = s.grade_id
            JOIN   sections sec  ON sec.id   = s.section_id
            JOIN   grades   g_sec ON g_sec.id = sec.grade_id
            WHERE  s.school_id = ?
              AND  s.grade_id != sec.grade_id
        ', [$this->school_id]);

        foreach ($mismatched as $row) {
            $correct = DB::selectOne('
                SELECT id FROM sections
                WHERE  school_id = ?
                  AND  grade_id  = ?
                  AND  LOWER(TRIM(name)) = LOWER(TRIM(?))
                ORDER  BY id ASC
                LIMIT  1
            ', [$this->school_id, $row->student_grade_id, $row->section_name]);

            $preview['mismatched'][] = [
                'student' => "#{$row->student_id} — {$row->student_name}",
                'student_grade' => $row->student_grade_name,
                'current_section' => "#{$row->section_id} — {$row->section_name} ({$row->section_grade_name})",
                'correct_section' => $correct ? "#{$correct->id}" : 'No matching section found — will remain',
            ];
        }

        $this->mergePreview = $preview;

        $this->dispatch('open-merge-preview-modal');
    }

    public function mergeDuplicateSections(): void
    {
        $mergedCount = 0;
        $fixedCount = 0;

        DB::transaction(function () use (&$mergedCount, &$fixedCount) {
            // ── STEP 1 ────────────────────────────────────────────────────────────
            // Cross-grade section merge.
            // Sections in different grade records that represent the same real grade
            // (same grade year + section name) duplicated across grade rows
            // are merged into the canonical section (lowest section ID).
            // Student grade_id is updated to the canonical grade as well.
            // This fixes "Year 6 has A B C D twice" caused by duplicate grade rows.
            // ─────────────────────────────────────────────────────────────────────
            $rows = DB::select('
                SELECT sec.id, sec.name AS section_name, sec.grade_id,
                       g.name  AS grade_name,
                       g.number AS grade_number
                FROM   sections sec
                JOIN   grades   g ON g.id = sec.grade_id
                WHERE  sec.school_id = ?
                ORDER  BY sec.id ASC
            ', [$this->school_id]);

            $crossGroups = collect($rows)->groupBy(function ($s) {
                $normName = preg_replace('/\s+/', '', strtolower($s->grade_name));
                $normLevel = (string) ($s->grade_number ?? '');
                $normSec = strtolower(trim($s->section_name));

                return "{$normName}__{$normLevel}__{$normSec}";
            });

            foreach ($crossGroups as $group) {
                if ($group->count() <= 1) {
                    continue;
                }

                // Prefer the canonical numbered grade; fall back to lowest section ID.
                $sorted = $group->sortByDesc(
                    fn ($s) => ((int) ($s->grade_number ?? 0)) * 1_000_000 - $s->id
                )->values();
                $keeper = $sorted->first();
                $duplicates = $sorted->slice(1);

                foreach ($duplicates as $dup) {
                    Student::where('section_id', $dup->id)
                        ->update(['section_id' => $keeper->id, 'grade_id' => $keeper->grade_id]);
                    Section::find($dup->id)->delete();
                    $mergedCount++;
                }
            }

            // ── STEP 1b ───────────────────────────────────────────────────────────
            // Grade consolidation.
            // Sections under non-canonical (malformatted) grade records that have
            // a canonical equivalent grade — reassign section.grade_id and
            // student.grade_id to the canonical grade when no naming conflict exists.
            // ─────────────────────────────────────────────────────────────────────
            $allGrades = DB::select('SELECT id, name, number FROM grades WHERE number IS NOT NULL');

            $gradeGroups = collect($allGrades)->groupBy(function ($g) {
                $normName = preg_replace('/\s+/', '', strtolower($g->name));
                $normLevel = (string) ($g->number ?? '');

                return "{$normName}__{$normLevel}";
            });

            foreach ($gradeGroups as $gradeGroup) {
                if ($gradeGroup->count() <= 1) {
                    continue;
                }

                $sortedGrades = $gradeGroup->sortByDesc(
                    fn ($g) => ((int) ($g->number ?? 0)) * 1_000_000 - $g->id
                )->values();

                $canonicalGrade = $sortedGrades->first();
                $nonCanonicalGrades = $sortedGrades->slice(1);

                foreach ($nonCanonicalGrades as $ncGrade) {
                    $ncSections = DB::select('
                        SELECT sec.id, sec.name AS section_name
                        FROM   sections sec
                        WHERE  sec.school_id = ?
                          AND  sec.grade_id  = ?
                    ', [$this->school_id, $ncGrade->id]);

                    foreach ($ncSections as $sec) {
                        // Skip if the canonical grade already has a section with the same name
                        // (Step 1 handles those with a merge+delete)
                        $conflict = DB::selectOne('
                            SELECT id FROM sections
                            WHERE  school_id = ?
                              AND  grade_id  = ?
                              AND  LOWER(TRIM(name)) = LOWER(TRIM(?))
                        ', [$this->school_id, $canonicalGrade->id, $sec->section_name]);

                        if ($conflict) {
                            continue;
                        }

                        // Reassign the section to the canonical grade
                        DB::table('sections')
                            ->where('id', $sec->id)
                            ->update(['grade_id' => $canonicalGrade->id]);

                        // Reassign the section's students to the canonical grade
                        Student::where('section_id', $sec->id)
                            ->update(['grade_id' => $canonicalGrade->id]);

                        $fixedCount++;
                    }
                }
            }

            // ── STEP 2 ────────────────────────────────────────────────────────────
            // Orphaned section fix.
            // A section is "orphaned" when it has students but none of them match
            // the section's own grade_id.  This happens when students are imported
            // with a different grade_id than the grade that owns the section.
            // Strategy:
            //   a) If another section with the same name already exists for the
            //      students' grade → move students/exams there, delete orphan.
            //   b) Otherwise → update the section's grade_id to the students' grade.
            // ─────────────────────────────────────────────────────────────────────
            $orphaned = DB::select('
                SELECT sec.id   AS section_id,
                       sec.name AS section_name,
                       sec.grade_id AS section_grade_id
                FROM   sections sec
                WHERE  sec.school_id = ?
                  AND  (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) > 0
                  AND  (SELECT COUNT(*) FROM students st
                        WHERE  st.section_id = sec.id
                          AND  st.grade_id   = sec.grade_id) = 0
            ', [$this->school_id]);

            foreach ($orphaned as $section) {
                $gradeRows = DB::select('
                    SELECT grade_id, COUNT(*) AS cnt
                    FROM   students
                    WHERE  section_id = ?
                    GROUP  BY grade_id
                ', [$section->section_id]);

                if (count($gradeRows) !== 1) {
                    // Students have mixed grades — skip; cannot safely reassign
                    continue;
                }

                $targetGradeId = $gradeRows[0]->grade_id;

                $existing = DB::selectOne('
                    SELECT id FROM sections
                    WHERE  school_id = ?
                      AND  grade_id  = ?
                      AND  LOWER(TRIM(name)) = LOWER(TRIM(?))
                      AND  id != ?
                    ORDER  BY id ASC
                    LIMIT  1
                ', [$this->school_id, $targetGradeId, $section->section_name, $section->section_id]);

                if ($existing) {
                    Student::where('section_id', $section->section_id)
                        ->update(['section_id' => $existing->id, 'grade_id' => $targetGradeId]);
                    Section::find($section->section_id)->delete();
                    $mergedCount++;
                } else {
                    // No collision — safely move the section to the correct grade
                    DB::table('sections')
                        ->where('id', $section->section_id)
                        ->update(['grade_id' => $targetGradeId]);
                    $fixedCount++;
                }
            }

            // ── STEP 3 ────────────────────────────────────────────────────────────
            // Classic same-grade duplicate section merge (safety-net pass).
            // After the above steps, catch any remaining sections that share the
            // same grade_id and the same (normalised) name within this school.
            // ─────────────────────────────────────────────────────────────────────
            $allSections = Section::where('school_id', $this->school_id)
                ->orderBy('id')
                ->get();

            $sameGradeGroups = $allSections->groupBy(
                fn ($s) => $s->grade_id.'__'.mb_strtolower(trim($s->name))
            );

            foreach ($sameGradeGroups as $group) {
                if ($group->count() <= 1) {
                    continue;
                }

                $keeper = $group->first();
                $duplicates = $group->slice(1);

                foreach ($duplicates as $dup) {
                    Student::where('section_id', $dup->id)
                        ->update(['section_id' => $keeper->id]);
                    Section::find($dup->id)->delete();
                    $mergedCount++;
                }
            }

            // ── STEP 4 ────────────────────────────────────────────────────────────
            // Fix any remaining mismatched students.
            // After the section fixes above, some students may still have a
            // grade_id that does not match their section's grade_id.  For each,
            // attempt to find the correct section by section name + student grade.
            // ─────────────────────────────────────────────────────────────────────
            $mismatched = DB::select('
                SELECT s.id  AS student_id,
                       s.grade_id AS student_grade_id,
                       sec.name   AS section_name
                FROM   students s
                JOIN   sections sec ON sec.id = s.section_id
                WHERE  s.school_id  = ?
                  AND  s.grade_id  != sec.grade_id
            ', [$this->school_id]);

            foreach ($mismatched as $row) {
                $correct = DB::selectOne('
                    SELECT id FROM sections
                    WHERE  school_id = ?
                      AND  grade_id  = ?
                      AND  LOWER(TRIM(name)) = LOWER(TRIM(?))
                    ORDER  BY id ASC
                    LIMIT  1
                ', [$this->school_id, $row->student_grade_id, $row->section_name]);

                if ($correct) {
                    DB::table('students')
                        ->where('id', $row->student_id)
                        ->update(['section_id' => $correct->id]);
                    $fixedCount++;
                }
            }
        });

        $total = $mergedCount + $fixedCount;

        if ($total === 0) {
            $this->dispatch('swal:toast', title: 'No issues found. All sections are already clean.', icon: 'info');
        } else {
            $parts = [];
            if ($mergedCount > 0) {
                $parts[] = "{$mergedCount} duplicate section(s) merged";
            }
            if ($fixedCount > 0) {
                $parts[] = "{$fixedCount} grade/section mismatch(es) fixed";
            }
            $this->dispatch('swal:toast', title: implode(', ', $parts).'.', icon: 'success');
        }
    }

    public function render()
    {
        $sections = Section::where('school_id', $this->school_id)
            ->when($this->searchWord, function ($query) {
                $query->where('name', 'LIKE', "%{$this->searchWord}%");
            })
            ->when($this->grade_id, function ($query) {
                $query->where('grade_id', $this->grade_id);
            })
            ->with('Grade')
            ->withCount('Student')
            ->orderBy('name', 'asc')
            ->get();

        $grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        return view('livewire.admin.schools.grades-and-sections', [
            'sections' => $sections,
            'grades' => $grades,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Sections/Division',
            'pageTitle' => 'Sections/Division',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Schools' => route('admin.schools'),
                'Classes' => '#',
            ],
        ]);
    }
}
