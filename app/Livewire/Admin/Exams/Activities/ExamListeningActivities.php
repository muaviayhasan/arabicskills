<?php

namespace App\Livewire\Admin\Exams\Activities;

use App\Models\Exam;
use App\Support\ExamActivityQuery;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExamListeningActivities extends Component
{
    public $exam;

    public $activities;

    public $inputs = [];

    public function mount(Exam $exam_id)
    {
        $this->exam = $exam_id;
        $this->activities = ExamActivityQuery::pickerCollection($this->exam, 'listening');

        foreach ($this->exam->listening_activities as $acts) {
            $this->inputs[$acts] = true;
        }
    }

    public function addActivities()
    {
        try {
            $ac = [];

            foreach ($this->inputs as $j => $in) {
                if ($in == true) {
                    $ac[] = $j;
                }
            }

            DB::beginTransaction();
            $this->exam->update(['listening_activities' => $ac]);
            DB::commit();

            $this->dispatch(
                'goEither',
                icon: 'success',
                title: 'Exam listening activities saved. Check exam writing assessment',
                cancel: route('admin.exams'),
                confirm: route('admin.exam-writing-activities', ['exam_id' => $this->exam->id]),
            );
        } catch (Exception $e) {
            DB::rollBack();

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view('livewire.admin.exams.activities.exam-listening-activities')->layout('layouts.base')->layoutData([
            'title' => 'Listening Activities',
            'pageTitle' => 'Listening Activities',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => route('admin.exams'),
                'Listening Activities' => '#',
            ],
        ]);
    }
}
