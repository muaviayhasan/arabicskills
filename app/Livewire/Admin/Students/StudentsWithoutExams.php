<?php

namespace App\Livewire\Admin\Students;

use App\Livewire\Concerns\WithTableSorting;
use App\Models\Exam;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentExam;
use Livewire\Component;
use Livewire\WithPagination;

class StudentsWithoutExams extends Component
{
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $school_id = '';

    public $term = '';

    public $schools;

    protected $queryString = [
        'school_id' => ['except' => ''],
        'term' => ['except' => ''],
    ];

    public function mount()
    {
        $this->schools = School::orderBy('name')->get(['id', 'name']);
    }

    public function updatedSchoolId()
    {
        $this->resetPage();
    }

    public function updatedTerm()
    {
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'students.name',
            'registration' => 'students.registration',
            'grade' => 'grades.number',
            'level' => 'levels.number',
            'section' => 'sections.name',
        ];
    }

    protected function applySortJoins($query)
    {
        $query->select('students.*');

        return match ($this->sortField) {
            'grade' => $query->leftJoin('grades', 'grades.id', '=', 'students.grade_id'),
            'level' => $query->leftJoin('levels', 'levels.id', '=', 'students.level_id'),
            'section' => $query->leftJoin('sections', 'sections.id', '=', 'students.section_id'),
            default => $query,
        };
    }

    public function render()
    {
        $students = collect();

        if ($this->school_id) {
            // Get all student IDs that have at least one student_exam record
            // for an active or pending exam at this school
            $activeExamIds = Exam::where('school_id', $this->school_id)
                ->whereIn('status', ['active', 'pending'])
                ->when($this->term, fn ($q) => $q->where('term', $this->term))
                ->pluck('id');

            $studentsWithExams = StudentExam::whereIn('exam_id', $activeExamIds)
                ->pluck('student_id')
                ->unique();

            $query = Student::where('students.school_id', $this->school_id)
                ->whereNotIn('students.id', $studentsWithExams)
                ->with(['Grade', 'Section', 'School', 'assignedLevel']);

            $students = $this->applySorting($query, 'students.name', 'asc')->paginate(30);
        }

        return view('livewire.admin.students.students-without-exams', [
            'students' => $students,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Students Without Exams',
            'pageTitle' => 'Students Without Exams',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Students' => route('admin.students'),
                'Without Exams' => '#',
            ],
        ]);
    }
}
