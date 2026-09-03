<?php

namespace App\Livewire\Admin\ExamCheck;

use App\Livewire\Concerns\RestrictsToAdminSchool;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class StudentExamDetails extends Component
{
    use RestrictsToAdminSchool;

    public $student_exam;

    public $status_array;

    public function mount($exam_id)
    {
        $this->student_exam = StudentExam::query()
            ->when($this->currentAdminSchoolId() !== null, function ($query) {
                $query->whereRelation('Exam', 'school_id', $this->currentAdminSchoolId());
            })
            ->with([
                'Exam' => function ($que) {
                    $que->with('School:id,name')->with('Grade:id,name');
                },
                'Student' => function ($que) {
                    $que->with([
                        'Section:id,name',
                        'Grade:id,name',
                        'School:id,name',
                        'assignedLevel:id,name',
                    ]);
                },
            ])->with('Result')->find($exam_id);

        if (! $this->student_exam) {
            return redirect()->route('admin.attempted-exams');
        }

        $this->status_array = ['reading' => 'reading_status', 'writing' => 'writing_status', 'listening' => 'listening_status', 'speaking' => 'speaking_status', 'sentences_structures' => 'sentences_structures_status'];

        foreach ($this->status_array as $status_type) {

            $time = $this->student_exam->{$status_type};

            if (! isset($time['status'])) {
                $time['status'] = 'unattempted';
                $this->student_exam->update([$status_type => array_merge($time, $this->student_exam->{$status_type})]);
            }

            if (isset($time['start_time'], $time['end_time'])) {
                $startTime = $time['start_time'];
                $endTime = $time['end_time'];

                if (! (Carbon::now()->between($startTime, $endTime)) && $time['status'] != 'attempted') {
                    $time['status'] = 'expired';

                    $this->student_exam->update([
                        $status_type => array_merge($this->student_exam->{$status_type}, $time),
                    ]);

                }
            }
        }
    }

    public function confirmReassign($activity)
    {
        $activityName = Str::headline(Str::replace('_status', '', $activity));

        $this->dispatch(
            'confirmDelete',
            title: 'Reassign Activity Confirmation',
            text: "Are you sure you want to reassign the {$activityName} activity? This will allow the student to retake this part of the exam.",
            // The global confirmDelete handler always dispatches back with `{ id: e.detail.id }`.
            // So we pass the activity key in `id`.
            id: $activity,
            emitBack: 'doReassignActivity',
            confirmButtonText: 'Yes, Reassign',
        );
    }

    #[On('doReassignActivity')]
    public function reassign($id)
    {
        $activity = $id;

        $this->student_exam->update([
            $activity => null,
        ]);

        $this->dispatch(
            'swal:alert',
            title: 'Student exam activity has been reassigned',
            icon: 'success',
            url: route('admin.student-exam.details', $this->student_exam->id)
        );

    }

    public function markChecked()
    {
        $this->dispatch(
            'confirmDelete',

            text: 'If the exam marked as checked, student result will be created and exam will be deleted',
            id: 0,
            emitBack: 'checkMark',
        );
    }

    #[On('checkMark')]
    public function checkMark($id)
    {

        $this->student_exam->checked = true;
        $this->student_exam->save();

        $answers = TakeExam::where('student_exam_id', $this->student_exam->id)->get();

        foreach ($answers as $key => $ans) {
            if ($ans->type == 'writing') {
                $anses = is_string($ans->answer)
                    ? unserialize($ans->answer)
                    : [];

                foreach ($anses as $an) {
                    delete_image($an);
                }
            } elseif ($ans->type == 'speaking') {
                delete_image($ans->answer);
            }
        }

        // TakeExam::where('student_exam_id', $this->student_exam->id)->delete();

        $this->dispatch(
            'swal:toast',
            title: 'Student exam has been moved to results.',
            icon: 'success',
            url: route('admin.attempted-exams')
        );

    }

    public function formatSkillDate(?string $datetime): ?string
    {
        if (! $datetime) {
            return null;
        }

        try {
            return Carbon::parse($datetime)->format('d M Y, H:i');
        } catch (\Exception $e) {
            return $datetime;
        }
    }

    public function getSkillSubmittedAt(string $skillKey, array $status): ?string
    {
        if (! empty($status['attempted_at'])) {
            return $this->formatSkillDate($status['attempted_at']);
        }

        if (($status['status'] ?? '') !== 'attempted') {
            return null;
        }

        $latest = TakeExam::where('student_exam_id', $this->student_exam->id)
            ->whereHas('Question.Activity', function ($query) use ($skillKey) {
                $query->where('type', $skillKey);
            })
            ->latest('updated_at')
            ->value('updated_at');

        return $latest ? $this->formatSkillDate($latest) : null;
    }

    public function getSkillDeviceIp(array $status): ?string
    {
        $ip = $status['device_ip'] ?? null;

        return is_string($ip) && $ip !== '' ? $ip : null;
    }

    public function render()
    {
        return view('livewire.admin.exam-check.student-exam-details')->layout('layouts.base')->layoutData([
            'title' => 'Student Exam Details',
            'pageTitle' => 'Student Exam Details',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Attempted Exams' => route('admin.attempted-exams'),
                'Student Exams' => '#',
            ],
        ]);
    }
}
