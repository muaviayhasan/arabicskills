<?php

namespace App\Livewire\Student\Concerns;

use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;

trait LoadsStudentRelations
{
    protected function authenticatedStudent(): Student
    {
        /** @var Student $student */
        $student = auth()->guard('web')->user();

        return $student->loadMissing(['Grade', 'Section', 'School', 'assignedLevel']);
    }

    protected function redirectIfPlacementIncomplete(): ?RedirectResponse
    {
        if (! Exam::studentHasResolvablePlacement($this->authenticatedStudent())) {
            return redirect()->route('student.exams')->with([
                'error' => 'Your profile is missing class or level information. Please contact your school administrator.',
            ]);
        }

        return null;
    }
}
