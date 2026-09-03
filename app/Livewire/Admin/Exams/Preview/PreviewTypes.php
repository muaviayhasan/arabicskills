<?php

namespace App\Livewire\Admin\Exams\Preview;

use App\Models\Exam;
use Livewire\Component;

class PreviewTypes extends Component
{
    public Exam $exam;

    public array $activityTranslations = [
        'reading' => 'فهم المقروء',
        'listening' => 'فهم المسموع',
        'writing' => 'التعبير الكتابي',
        'speaking' => 'مهارة التحدث',
        'sentences_structures' => 'فهم بنية الجمل',
    ];

    public array $activityLabels = [
        'reading' => 'Reading Comprehension',
        'listening' => 'Listening Comprehension',
        'writing' => 'Writing',
        'speaking' => 'Speaking',
        'sentences_structures' => 'Sentence Structures',
    ];

    public function mount(Exam $exam): void
    {
        $this->exam = $exam->load(['School', 'Grade', 'Section']);
    }

    public function render()
    {
        return view('livewire.admin.exams.preview.preview-types')
            ->layout('layouts.app')
            ->layoutData([
                'title' => 'Preview Exam',
            ]);
    }
}
