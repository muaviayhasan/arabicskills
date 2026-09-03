<?php

namespace App\Livewire\Admin\QuestionBanks;

use App\Models\Grade;
use App\Models\Level;
use Livewire\Component;

class AddQuestionBank extends Component
{
    public $grades;

    public $levels;

    public $type;

    public function mount($type)
    {
        $this->grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $this->levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $this->type = $type;
    }

    public function render()
    {
        $t = ucwords(str_replace('_', ' ', $this->type));

        return view('livewire.admin.question-banks.add-question-bank')->layout('layouts.base')->layoutData([
            'title' => 'Add '.$t.' Activity Question',
            'pageTitle' => 'Add '.$t.' Activity Question',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Question Banks' => route('admin.question-banks'),
                'Add' => '#',
            ],
        ]);
    }
}
