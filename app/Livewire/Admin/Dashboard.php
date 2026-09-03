<?php

namespace App\Livewire\Admin;

use App\Models\Activity;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use Livewire\Component;

class Dashboard extends Component
{
    public $schools;
    public $students;
    public $grades;
    public $sections;
    public $banks;
    public $exams;
    public $admins;

    public function mount()
    {
        $this->schools = School::count();
        $this->students = Student::count();
        $this->grades = Grade::count();
        $this->sections = Section::count();
        $this->banks = Activity::count();
        $this->exams = Exam::count();
        $this->admins = Admin::count();
    }
    public function render()
    {
        return view('livewire.admin.dashboard')->layout('layouts.base')->layoutData([
            'title' => 'Dashboard',
            'pageTitle' => 'Dashboard',
            'breadcrumb' => [
                'Dashboard' => '#',
            ],
        ]);
    }
}
