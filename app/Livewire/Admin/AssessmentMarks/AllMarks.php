<?php

namespace App\Livewire\Admin\AssessmentMarks;

use App\Livewire\Concerns\RestrictsToAdminSchool;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\AssessmentMark;
use App\Models\School;
use App\Support\MarkRanges;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Imported assessment marks: the marks, their judgments, the total and the
 * expectation for the next round, alongside each student's details.
 */
class AllMarks extends Component
{
    use RestrictsToAdminSchool;
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $school_id = '';

    public $academic_year = '';

    public $round = '';

    public $searchWord = '';

    protected $queryString = [
        'school_id' => ['except' => ''],
        'academic_year' => ['except' => ''],
        'round' => ['except' => ''],
        'searchWord' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->academic_year = current_academic_year();
        $this->school_id = $this->currentAdminSchoolId() ?: '';
    }

    public function updated($property): void
    {
        if (in_array($property, ['school_id', 'academic_year', 'round', 'searchWord'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['round', 'searchWord']);
        $this->academic_year = current_academic_year();
        $this->school_id = $this->currentAdminSchoolId() ?: '';
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'student' => 'students.name',
            'registration' => 'students.registration',
            'grade' => 'grades.number',
            'round' => 'assessment_marks.round',
            'total' => 'assessment_marks.total',
        ];
    }

    protected function applySortJoins($query)
    {
        $query->select('assessment_marks.*');

        return match ($this->sortField) {
            'student', 'registration' => $query->leftJoin('students', 'students.id', '=', 'assessment_marks.student_id'),
            'grade' => $query
                ->leftJoin('students', 'students.id', '=', 'assessment_marks.student_id')
                ->leftJoin('grades', 'grades.id', '=', 'students.grade_id'),
            default => $query,
        };
    }

    public function render()
    {
        $adminSchoolId = $this->currentAdminSchoolId();

        if ($adminSchoolId !== null) {
            $this->school_id = $adminSchoolId;
        }

        $query = AssessmentMark::query()
            ->with(['Student.School', 'Student.Grade', 'Student.Section', 'Student.assignedLevel'])
            ->when($this->academic_year, fn ($q) => $q->where('academic_year', $this->academic_year))
            ->when($this->round, fn ($q) => $q->where('round', $this->round))
            ->when($this->school_id, fn ($q) => $q->whereHas(
                'Student',
                fn ($student) => $student->where('school_id', $this->school_id)
            ))
            ->when(trim((string) $this->searchWord) !== '', fn ($q) => $q->whereHas(
                'Student',
                fn ($student) => $student
                    ->where('name', 'LIKE', "%{$this->searchWord}%")
                    ->orWhere('registration', 'LIKE', "%{$this->searchWord}%")
            ));

        $marks = $this->applySorting($query, 'assessment_marks.id', 'desc')->paginate(20);

        return view('livewire.admin.assessment-marks.all-marks', [
            'marks' => $marks,
            'schools' => $adminSchoolId
                ? School::where('id', $adminSchoolId)->get()
                : School::orderBy('name')->get(),
            'years' => range(current_academic_year() - 3, current_academic_year() + 1),
            'rounds' => MarkRanges::ROUNDS,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Assessment Marks',
            'pageTitle' => 'Assessment Marks',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Assessment Marks' => '#',
            ],
        ]);
    }
}
