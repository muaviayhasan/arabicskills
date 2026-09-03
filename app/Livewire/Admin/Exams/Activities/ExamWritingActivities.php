<?php

namespace App\Livewire\Admin\Exams\Activities;

use App\Models\Exam;
use App\Support\ExamActivityQuery;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExamWritingActivities extends Component
{
    public $exam;

    public $activities;

    public $inputs = [];

    public function mount(Exam $exam_id)
    {
        $this->exam = $exam_id;
        $this->activities = ExamActivityQuery::pickerCollection($this->exam, 'writing');

        foreach ($this->exam->writing_activities as $acts) {
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
            $this->exam->update(['writing_activities' => $ac]);
            DB::commit();

            $this->dispatch(
                'goEither',
                icon: 'success',
                title: 'Exam writing activities saved. Check exam speaking assessment',
                cancel: route('admin.exams'),
                confirm: route('admin.exam-speaking-activities', ['exam_id' => $this->exam->id]),
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
        return view('livewire.admin.exams.activities.exam-writing-activities')->layout('layouts.base')->layoutData([
            'title' => 'Writing Activities',
            'pageTitle' => 'Writing Activities',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => route('admin.exams'),
                'Writing Activities' => '#',
            ],
        ]);
    }
}
