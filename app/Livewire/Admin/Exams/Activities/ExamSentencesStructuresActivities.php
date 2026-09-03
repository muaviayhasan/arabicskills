<?php

namespace App\Livewire\Admin\Exams\Activities;

use App\Models\Exam;
use App\Support\ExamActivityQuery;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExamSentencesStructuresActivities extends Component
{
    public $exam;

    public $activities;

    public $inputs = [];

    public function mount(Exam $exam_id)
    {
        $this->exam = $exam_id;
        $this->activities = ExamActivityQuery::pickerCollection($this->exam, 'sentences_structures');

        foreach ($this->exam->sentences_structures_activities as $acts) {
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

            $this->exam->update(['sentences_structures_activities' => $ac]);

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Exam sentence structures activities saved.',
                url: route('admin.exams'),
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
        return view('livewire.admin.exams.activities.exam-sentences-structures-activities')->layout('layouts.base')->layoutData([
            'title' => 'Sentences Structures Activities',
            'pageTitle' => 'Sentences Structures Activities',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => route('admin.exams'),
                'Sentences Structures Activities' => '#',
            ],
        ]);
    }
}
