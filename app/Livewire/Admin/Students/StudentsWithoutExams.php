<?php

namespace App\Livewire\Admin\Students;

use App\Models\Exam;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentExam;
use Livewire\Component;
use Livewire\WithPagination;

class StudentsWithoutExams extends Component
{
    use WithPagination;

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

            $students = Student::where('school_id', $this->school_id)
                ->whereNotIn('id', $studentsWithExams)
                ->with(['Grade', 'Section', 'School', 'assignedLevel'])
                ->orderBy('name')
                ->paginate(30);
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
