<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\LoadsStudentRelations;
use Livewire\Component;

class StudentDashboard extends Component
{
    use LoadsStudentRelations;

    public function mount(): void
    {
        $this->authenticatedStudent();
    }

    public function render()
    {
        return view('livewire.student.student-dashboard')->layout('layouts.app')->layoutData([
            'title' => 'Dashboard',
        ]);
    }
}
