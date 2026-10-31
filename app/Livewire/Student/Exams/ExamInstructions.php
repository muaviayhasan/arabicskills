<?php

namespace App\Livewire\Student\Exams;

use App\Models\Instruction;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExamInstructions extends Component
{
    /**
     * The skills a student can be shown instructions for. The type arrives
     * from the URL, and the page builds a route name out of it, so anything
     * not on this list is a 404 rather than a broken page.
     */
    public const SKILLS = ['reading', 'listening', 'writing', 'speaking', 'sentences_structures'];

    public $type;

    public $instructions;

    public function mount($type)
    {
        if (! in_array($type, self::SKILLS, true)) {
            throw new NotFoundHttpException;
        }

        $this->type = $type;

        // A skill whose instructions have not been written yet leaves the
        // panel empty; it must not stop the student reaching the paper.
        $this->instructions = Instruction::where('page', "{$type}_exam_instructions")->value('instructions') ?? '';
    }

    public function render()
    {
        return view('livewire.student.exams.exam-instructions')->layout('layouts.app')->layoutData([
            'title' => 'Exam Instructions',
        ]);
    }
}
