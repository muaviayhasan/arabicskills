<?php

namespace App\Livewire\Student\Concerns;

use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

trait LoadsStudentRelations
{
    protected function authenticatedStudent(): Student
    {
        /** @var Student $student */
        $student = auth()->guard('web')->user();

        return $student->loadMissing(['Grade', 'Section', 'School', 'assignedLevel']);
    }

    /**
     * Inside a Livewire component redirect() hands back Livewire's own
     * Redirector, not a RedirectResponse. Declaring only the latter turned
     * every unplaced student's exam page into a 500 instead of the message
     * below.
     */
    protected function redirectIfPlacementIncomplete(): Redirector|RedirectResponse|null
    {
        if (! Exam::studentHasResolvablePlacement($this->authenticatedStudent())) {
            return redirect()->route('student.exams')->with([
                'error' => 'Your profile is missing class or level information. Please contact your school administrator.',
            ]);
        }

        return null;
    }
}
