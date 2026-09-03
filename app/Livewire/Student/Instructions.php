<?php

namespace App\Livewire\Student;

use App\Models\Instruction;
use Livewire\Component;

class Instructions extends Component
{
    public $instructions;

    public function mount()
    {
        $this->instructions = Instruction::where('page', 'dashboard_instructions')->first()->instructions;
    }
    public function render()
    {
        return view('livewire.student.instructions')->layout('layouts.app')->layoutData([
            'title' => 'Instructions',
        ]);
    }
}
