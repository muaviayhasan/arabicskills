<?php

namespace App\Livewire\Admin\QuestionBanks;

use App\Models\Activity;
use App\Models\Grade;
use App\Models\Level;
use App\Models\Question;
use Livewire\Component;

class EditQuestionBank extends Component
{
    public $activity;

    public $grades;

    public $levels;

    public $matchOptions;

    public function mount($activity_id)
    {
        $this->activity = Activity::with('Question')->findOrFail($activity_id);

        $this->grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $this->levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);
    }

    protected $listeners = [
        'questionDelete',
    ];

    public function deleteImage()
    {
        delete_image($this->activity->image);

        $this->activity->image = '';
        $this->activity->save();

        $this->dispatch(
            'swal:toast',
            title: 'Image deleted successfully.',
            icon: 'success',
            url: route('admin.edit-question-bank', ['activity_id' => $this->activity->id])
        );
    }

    public function deleteQuestion($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'Question will be deleted permanently.',
            id: $id,
            emitBack: 'questionDelete',
        );
    }

    public function questionDelete($id)
    {
        $q = Question::find($id);
        delete_image($q->image);
        if ($q->type == 'match') {
            foreach ($q->options['choice'] ?? [] as $ac) {
                delete_image($ac);
            }

            foreach ($q->options['answer'] ?? [] as $ac) {
                delete_image($ac);
            }
        }
        $q->delete();
        $this->dispatch(
            'swal:toast',
            title: 'Question deleted successfully.',
            icon: 'success',
        );
    }

    public function render()
    {
        $t = ucwords(str_replace('_', ' ', $this->activity?->type ?? ''));

        return view('livewire.admin.question-banks.edit-question-bank')->layout('layouts.base')->layoutData([
            'title' => 'Edit '.$t.' Activity Question',
            'pageTitle' => 'Edit '.$t.' Activity Question',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Question Banks' => route('admin.question-banks'),
                'Edit' => '#',
            ],
        ]);
    }
}
