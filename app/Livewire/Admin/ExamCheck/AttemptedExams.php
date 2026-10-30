<?php

namespace App\Livewire\Admin\ExamCheck;

use App\Exports\AttemptedExamsExport;
use App\Livewire\Concerns\RestrictsToAdminSchool;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentExam;
use App\Support\ExamSkillStatus;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class AttemptedExams extends Component
{
    use RestrictsToAdminSchool;
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $searchWord;

    public $school_id;

    public $grade_id;

    public $level_id;

    public $term;

    public $status;

    public $year;

    public $archiveStatus = 'active';

    public $inputs = [
        'school_id' => null,
        'activities' => [],   // ✅ MUST be array
    ];

    protected $queryString = [
        'school_id' => ['except' => ''],
        'grade_id' => ['except' => ''],
        'level_id' => ['except' => ''],
        'term' => ['except' => ''],
        'status' => ['except' => ''],
        'year' => ['except' => ''],
        'archiveStatus' => ['except' => 'active'],
    ];

    protected $listeners = [
        'examDelete',
    ];

    /**
     * Prevent re-running expensive sync work on every re-render.
     * This flag is reset whenever relevant filters change.
     */
    protected bool $didSyncMissingStudentExams = false;

    public function mount()
    {
        $adminSchoolId = $this->currentAdminSchoolId();
        if ($adminSchoolId !== null) {
            $this->school_id = $adminSchoolId;
        }
    }

    public function updatedSchoolId()
    {
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    public function updatedGradeId()
    {
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    public function updatedLevelId()
    {
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    public function updatedTerm()
    {
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function updatedYear()
    {
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    public function updatedArchiveStatus()
    {
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    public function manageSearch()
    {
        $this->resetPage();

    }

    public function resetFilters()
    {
        if ($this->currentAdminSchoolId() === null) {
            $this->school_id = '';
        }
        $this->grade_id = '';
        $this->level_id = '';
        $this->term = '';
        $this->status = '';
        $this->year = '';
        $this->archiveStatus = 'active';
        $this->didSyncMissingStudentExams = false;
        $this->resetPage();
    }

    /**
     * Ensure StudentExam rows exist for students matching current filters.
     * Uses student-centric reconcile so only one resolved link remains per term.
     */
    private function syncMissingStudentExams(): void
    {
        $adminSchoolId = $this->currentAdminSchoolId();

        if (empty($this->grade_id)) {
            return;
        }

        Student::query()
            ->where('grade_id', $this->grade_id)
            ->whereNotNull('level_id')
            ->when($adminSchoolId !== null, fn ($q) => $q->where('school_id', $adminSchoolId))
            ->when($adminSchoolId === null && ! empty($this->school_id), fn ($q) => $q->where('school_id', $this->school_id))
            ->when(! empty($this->level_id), fn ($q) => $q->where('level_id', $this->level_id))
            ->orderBy('id')
            ->chunkById(200, function ($students) {
                foreach ($students as $student) {
                    Exam::reconcileStudentExams($student);
                }
            });
    }

    public function reassignAllExpired()
    {
        $columns = [
            'reading_status',
            'listening_status',
            'writing_status',
            'speaking_status',
            'sentences_structures_status',
        ];

        StudentExam::query()
            ->where(function ($query) use ($columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', '%expired%');
                }
            })
            ->chunkById(200, function ($exams) use ($columns) {
                foreach ($exams as $exam) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $status = $exam->{$column};

                        if (is_array($status) && ($status['status'] ?? null) === 'expired') {
                            $updates[$column] = null;
                        }
                    }

                    if ($updates) {
                        StudentExam::where('id', $exam->id)->update($updates);
                    }
                }
            });

    }

    /**
     * The exam behind a row, as this admin is allowed to see it. An admin tied
     * to one school must not reach another school's student by id.
     */
    private function findReopenableExam(int $studentExamId): ?StudentExam
    {
        return StudentExam::query()
            ->when($this->currentAdminSchoolId() !== null, function ($query) {
                $query->whereRelation('Exam', 'school_id', $this->currentAdminSchoolId());
            })
            ->where('checked', false)
            ->with(['Student', 'Exam'])
            ->withCount('TakeExam')
            ->find($studentExamId);
    }

    /**
     * The skills of one row an admin can hand back to the student.
     *
     * @return array<string, string>
     */
    public function reopenableSkills(StudentExam $exam): array
    {
        return ExamSkillStatus::reopenableSkills($exam, ($exam->take_exam_count ?? 0) > 0);
    }

    public function confirmReopen($studentExamId)
    {
        if (! getPermissions('results', 'edit')) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'You do not have permission to reopen an exam.'
            );

            return;
        }

        $exam = $this->findReopenableExam((int) $studentExamId);

        if (! $exam) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'That exam is no longer on this list.'
            );

            return;
        }

        $skills = $this->reopenableSkills($exam);

        if ($skills === []) {
            $this->dispatch(
                'swal:alert',
                icon: 'info',
                title: 'Nothing to reopen',
                text: 'Every skill on this exam is either still open to the student or already answered. An answered skill is reopened one at a time from the exam details.'
            );

            return;
        }

        $student = $exam->Student?->name ?: 'this student';
        $text = implode(', ', $skills).' will be unlocked for '.$student
            .'. The clock starts again from zero when they open each one, and anything they have already answered is kept.';

        if ($exam->Exam?->status !== 'active') {
            $text .= ' The exam itself is '.($exam->Exam?->status ?? 'not set')
                .', so they still cannot sit it until the exam is set back to active.';
        }

        $this->dispatch(
            'confirmReopen',
            title: 'Reopen this exam?',
            text: $text,
            id: $exam->id,
            emitBack: 'doReopenExam',
            confirmButtonText: 'Yes, reopen',
        );
    }

    #[On('doReopenExam')]
    public function reopen($id)
    {
        if (! getPermissions('results', 'edit')) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'You do not have permission to reopen an exam.'
            );

            return;
        }

        $exam = $this->findReopenableExam((int) $id);

        if (! $exam) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'That exam is no longer on this list.'
            );

            return;
        }

        $skills = $this->reopenableSkills($exam);

        if ($skills === []) {
            $this->dispatch(
                'swal:alert',
                icon: 'info',
                title: 'Nothing to reopen'
            );

            return;
        }

        // Cleared through the query builder so the column holds a real null,
        // the state a skill is in before it is ever started.
        StudentExam::where('id', $exam->id)->update(array_fill_keys(array_keys($skills), null));

        $this->dispatch(
            'swal:alert',
            icon: 'success',
            title: 'Exam reopened',
            text: implode(', ', $skills).' can be sat again by '.($exam->Student?->name ?: 'the student').'.'
        );
    }

    public function exportSearched()
    {
        // Respect admin school restriction (if any)
        $adminSchoolId = $this->currentAdminSchoolId();
        if ($adminSchoolId !== null) {
            $this->school_id = $adminSchoolId;
        }

        // Ensure missing StudentExam rows exist so "Absent" students appear in export.
        $this->syncMissingStudentExams();

        $filters = [
            'school_id' => $this->school_id,
            'grade_id' => $this->grade_id,
            'level_id' => $this->level_id,
            'term' => $this->term,
            'status' => $this->status,
            'year' => $this->year,
            'archiveStatus' => $this->archiveStatus,
            'searchWord' => $this->searchWord,
        ];

        $fileName = 'attempted-exams-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new AttemptedExamsExport($filters), $fileName);
    }

    /**
     * Level is deliberately absent: exams.level_ids is a JSON array, so there
     * is no single value to order by.
     *
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'school' => 'schools.name',
            'term' => 'exams.term',
            'student' => 'students.name',
            'date' => 'student_exams.created_at',
        ];
    }

    protected function applySortJoins($query)
    {
        // No select() here: withCount('TakeExam') has already selected
        // student_exams.* alongside its aggregate, and re-selecting would
        // drop the count that marks a student absent.
        return match ($this->sortField) {
            'school' => $query
                ->leftJoin('exams', 'exams.id', '=', 'student_exams.exam_id')
                ->leftJoin('schools', 'schools.id', '=', 'exams.school_id'),
            'term' => $query->leftJoin('exams', 'exams.id', '=', 'student_exams.exam_id'),
            'student' => $query->leftJoin('students', 'students.id', '=', 'student_exams.student_id'),
            default => $query,
        };
    }

    public function render()
    {
        $adminSchoolId = $this->currentAdminSchoolId();
        if ($adminSchoolId !== null) {
            $this->school_id = $adminSchoolId;
        }

        // Sync once per Livewire request cycle (reset when filters change).
        if (! $this->didSyncMissingStudentExams) {
            $this->syncMissingStudentExams();
            $this->didSyncMissingStudentExams = true;
        }

        $exams = StudentExam::query()
            ->whereHas('Student', function ($query) {
                $query->applyArchiveFilters($this->archiveStatus, $this->year);
            })
            ->whereHas('Exam', function ($query) {
                $query->applyArchiveFilters($this->archiveStatus);
            })

            // 🔍 Filter by Exam → School
            ->when($this->school_id, function ($query) {
                $query->whereRelation('Exam', 'school_id', $this->school_id);
            })

            // 🔍 Filter by Exam → Grade
            ->when($this->grade_id, function ($query) {
                $query->whereRelation('Exam', 'grade_id', $this->grade_id);
            })

            // 🔍 Filter by Exam → Level
            ->when($this->level_id, function ($query) {
                $query->whereHas('Exam', function ($q) {
                    $q->whereJsonContains('level_ids', (int) $this->level_id);
                });
            })

            ->when($this->term, function ($query) {
                $query->whereRelation('Exam', 'term', $this->term);
            })

            ->when($this->status, function ($query) {
                $query->whereAnySkillStatus($this->status);
            })

            // 🔍 Search by Student name, registration, or username
            ->when($this->searchWord, function ($query) {
                $query->whereHas('Student', function ($q) {
                    $q->applyArchiveFilters($this->archiveStatus, $this->year)
                        ->where(function ($sub) {
                            $sub->where('name', 'like', '%'.$this->searchWord.'%')
                                ->orWhere('registration', 'like', '%'.$this->searchWord.'%')
                                ->orWhere('user_name', 'like', '%'.$this->searchWord.'%');
                        });
                });
            })

            // ✅ Only unchecked exams
            ->where('checked', false)

            // 📦 Eager loads
            ->with([
                'Exam' => function ($q) {
                    $q->applyArchiveFilters($this->archiveStatus)
                        ->select(
                            'id',
                            'term',
                            'school_id',
                            'grade_id',
                            'level_ids',
                            'status',
                            'deleted_at'
                        )
                        ->with([
                            'School:id,name',
                            'Grade:id,name',
                        ]);
                },
                'Student' => function ($q) {
                    $q->applyArchiveFilters($this->archiveStatus, $this->year)
                        ->select('id', 'name', 'registration', 'user_name', 'section_id', 'deleted_at');
                },
                'Student.Section:id,name',
                'Result',
            ])

            // Used to mark "Absent" in the list when no answers exist.
            ->withCount('TakeExam')

            ->tap(fn ($query) => $this->applySorting($query, 'student_exams.id', 'desc'))
            ->paginate(20);

        // 🔽 Filter dropdown data (same as Exam listing)
        $schools = School::select('id', 'name')
            ->when($adminSchoolId !== null, function ($query) use ($adminSchoolId) {
                $query->whereKey($adminSchoolId);
            })
            ->orderBy('name')
            ->get();

        $gradeIds = Exam::query()
            ->applyArchiveFilters($this->archiveStatus)
            ->distinct()
            ->pluck('grade_id');

        $grades = Grade::select('id', 'name', 'number')
            ->whereIn('id', $gradeIds)
            ->get()
            ->sortBy('number', SORT_NATURAL);

        $levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $terms = collect(unserialize(config('options.terms')) ?: [])
            ->merge(
                Exam::query()
                    ->applyArchiveFilters($this->archiveStatus)
                    ->select('term')
                    ->whereNotNull('term')
                    ->distinct()
                    ->pluck('term')
            )
            ->filter()
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('livewire.admin.exam-check.attempted-exams', [
            'exams' => $exams,
            'schools' => $schools,
            'grades' => $grades,
            'levels' => $levels,
            'terms' => $terms,
        ])
            ->layout('layouts.base')
            ->layoutData([
                'title' => 'Attempted Exams',
                'pageTitle' => 'Attempted Exams',
                'breadcrumb' => [
                    'Dashboard' => route('admin.dashboard'),
                    'Attempted Exams' => '#',
                ],
            ]);
    }
}
