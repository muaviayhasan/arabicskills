<?php

namespace App\Livewire\Admin\Exams\Activities;

use App\Models\Exam;
use App\Support\ExamActivityQuery;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExamReadingActivities extends Component
{
    public $exam;

    public $activities;

    public $inputs = [];

    public function mount(Exam $exam_id)
    {
        $this->exam = $exam_id;
        $this->activities = ExamActivityQuery::pickerCollection($this->exam, 'reading');

        foreach ($this->exam->reading_activities as $acts) {
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

            $this->exam->update(['reading_activities' => $ac]);

            DB::commit();

            $this->dispatch(
                'goEither',
                icon: 'success',
                title: 'Exam reading activities saved. Check exam listening assessment',
                cancel: route('admin.exams'),
                confirm: route('admin.exam-listening-activities', ['exam_id' => $this->exam->id]),
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
        return view('livewire.admin.exams.activities.exam-reading-activities')->layout('layouts.base')->layoutData([
            'title' => 'Reading Activities',
            'pageTitle' => 'Reading Activities',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => route('admin.exams'),
                'Reading Activities' => '#',
            ],
        ]);
    }
}
