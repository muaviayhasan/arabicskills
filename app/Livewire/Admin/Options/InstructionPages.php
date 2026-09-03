<?php

namespace App\Livewire\Admin\Options;

use Exception;
use Livewire\Component;
use App\Models\Instruction;
use Illuminate\Support\Facades\DB;

class InstructionPages extends Component
{
    public $inputs = [];
    public $instructions;
    protected $listeners = ['updateInput'];

    public function updateInput($inputId, $contents)
    {
        $this->inputs[$inputId] = $contents;
    }
    public function mount()
    {
        $this->instructions = Instruction::all();
        foreach ($this->instructions as $inst) {
            $this->inputs[$inst->page] = $inst->instructions;
        }

        if (!isset($this->inputs['sentences_structures_exam_instructions'])) {
            
            $ins = Instruction::updateOrCreate(
                ['page' => 'sentences_structures_exam_instructions'],
                [
                    'instructions' => '<h2 class="mb-4 fw-bold">Add Sentences Structures Exam Instructions Here.</h2>'
                ]
            );

            $this->inputs[$ins->page] = $ins->instructions;

        }
    }

    public function save()
    {
        $this->validate([
            'inputs.dashboard_instructions' => "required",
            'inputs.reading_exam_instructions' => "required",
            'inputs.listening_exam_instructions' => "required",
            'inputs.writing_exam_instructions' => "required",
            'inputs.speaking_exam_instructions' => "required",
            'inputs.sentences_structures_exam_instructions' => "required",
        ]);

        try {
            DB::beginTransaction();

            foreach ($this->instructions as $inst) {
                $inst->update([
                    'instructions' => $this->inputs[$inst->page]
                ]);
            }
            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Instructions updated successfully',
                url: route('admin.instruction-pages')
            );
        } catch (Exception $e) {

            DB::rollback();


            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }
    public function render()
    {
        return view('livewire.admin.options.instruction-pages')->layout('layouts.base')->layoutData([
            'title' => 'Student Instruction Pages',
            'pageTitle' => 'Student Instruction Pages',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Student Instruction Pages' => route('admin.instruction-pages'),
            ],
        ]);
    }
}
