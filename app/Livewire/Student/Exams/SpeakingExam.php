<?php

namespace App\Livewire\Student\Exams;

use App\Livewire\Student\Concerns\LoadsStudentRelations;
use App\Models\Activity;
use App\Models\Exam;
use App\Models\StudentExam;
use App\Support\ExamSkillStatus;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class SpeakingExam extends Component
{
    use LoadsStudentRelations;

    public $student_exam;

    public $activities = [];

    public $inputs = [];

    public $time = [];

    public $timer = true;

    public $remainingSeconds;

    public function mount()
    {
        if ($redirect = $this->redirectIfPlacementIncomplete()) {
            return $redirect;
        }

        $std = $this->authenticatedStudent();
        Exam::reconcileStudentExams($std);
        $exam = Exam::resolveLatestForStudent($std, ['active']);

        if (! $exam) {
            return redirect()->route('student.exams')->with(['error' => 'Student Exam Not Exist']);
        }

        $this->student_exam = StudentExam::where('student_id', $std->id)
            ->where('exam_id', $exam->id)
            ->where('checked', false)
            ->orderBy('created_at', 'desc')
            ->first();

        if (! $this->student_exam) {
            return redirect()->route('student.exams')->with(['error' => 'Student Exam Not Exist']);
        }

        $exam = Exam::find($this->student_exam->exam_id);

        $acties = $exam->speaking_activities;
        if ($acties == [] || $exam->status != 'active') {
            return redirect()->route('student.exams')->with(['error' => 'Exam has no questions']);
        }

        $this->time = $this->student_exam->speaking_status ?? [];
        if (! isset($this->time['status']) || $this->time['status'] === 'unattempted') {
            if (! isset($this->time['status'])) {
                $this->time['status'] = 'unattempted';
            }
            if (! isset($this->time['start_time'], $this->time['end_time'])) {
                [$hours_to_add, $minutes_to_add] = explode(':', $exam->speaking_time);
                $this->time['start_time'] = Carbon::now()->format('Y-m-d H:i:s');
                $this->time['end_time'] = Carbon::now()->copy()->addHours($hours_to_add)->addMinutes($minutes_to_add)->format('Y-m-d H:i:s');
            }
            $this->time = ExamSkillStatus::withDeviceIp($this->time);
            $this->student_exam->speaking_status = $this->time;
            $this->student_exam->save();
        } elseif ($this->time['status'] !== 'unattempted') {
            return redirect()->route('student.exams')->with(['error' => 'You have attempted this exam before']);
        }

        if (isset($this->time['start_time'], $this->time['end_time'])) {
            $startTime = Carbon::parse($this->time['start_time']);
            $endTime = Carbon::parse($this->time['end_time']);

            if (! Carbon::now()->between($startTime, $endTime)) {
                $this->time['status'] = 'expired';
                $this->student_exam->speaking_status = $this->time;
                $this->student_exam->save();

                return redirect()->route('student.exams')->with(['error' => 'Exam Expired. You started at '.$this->time['start_time'].' to '.$this->time['end_time']]);
            }
        }

        $this->time['start_time'] = Carbon::parse($this->time['start_time']);
        $this->time['end_time'] = Carbon::parse($this->time['end_time']);
        if (is_array($acties)) {
            $this->activities = Activity::with('Question')->findMany($acties)
                ->sortBy(fn ($activity) => array_search($activity->id, $acties));
        }

        $this->activities = collect($this->activities);

        $start = Carbon::parse($this->time['start_time']);
        $end = Carbon::parse($this->time['end_time']);

        // Remaining seconds (never negative)
        $this->remainingSeconds = max(now()->diffInSeconds($end, false), 0);
    }

    #[On('expireExam')]
    public function expireExam()
    {
        $this->timer = false;
        $this->time['status'] = 'attempted';

        $this->student_exam->update([
            'speaking_status' => array_merge($this->student_exam->speaking_status, $this->time),
        ]);

        $this->dispatch(
            'form_save',
            title: 'Time is Up',
            formId: '#speaking_exam',
        );
    }

    public function render()
    {

        return view('livewire.student.exams.speaking-exam')->layout('layouts.app')->layoutData([
            'title' => 'Speaking Exam',
        ]);
    }
}
