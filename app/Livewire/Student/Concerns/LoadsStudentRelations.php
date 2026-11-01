<?php

namespace App\Livewire\Student\Concerns;

use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
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
     * What this student has already recorded against this attempt, keyed by
     * question id.
     *
     * A reopened paper starts again on a fresh clock, and without this the
     * student would open it to a blank form and have to redo work they had
     * already done, which is the opposite of what reopening is for. Blank
     * rows are left out: a paper that auto-submitted writes an empty row for
     * every textarea on it, and those are not answers.
     *
     * @return array<int, string>
     */
    protected function recordedAnswers(?StudentExam $attempt): array
    {
        if ($attempt === null) {
            return [];
        }

        return TakeExam::query()
            ->where('student_exam_id', $attempt->id)
            ->whereNotNull('answer')
            ->whereNotIn('answer', ['', '{}'])
            ->pluck('answer', 'question_id')
            ->all();
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
