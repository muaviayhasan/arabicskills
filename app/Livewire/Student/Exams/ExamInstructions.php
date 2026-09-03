<?php

namespace App\Livewire\Student\Exams;

use App\Models\Instruction;
use Livewire\Component;

class ExamInstructions extends Component
{
    public $type;

    public $instructions;

    public function mount($type)
    {
        $this->type = $type;
        $this->instructions = Instruction::where('page', "{$type}_exam_instructions")->first()->instructions;
    }

    public function render()
    {
        return view('livewire.student.exams.exam-instructions')->layout('layouts.app')->layoutData([
            'title' => 'Exam Instructions',
        ]);
    }
}
