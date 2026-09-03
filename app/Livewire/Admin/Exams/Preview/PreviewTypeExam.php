<?php

namespace App\Livewire\Admin\Exams\Preview;

use App\Models\Activity;
use App\Models\Exam;
use Illuminate\Support\Collection;
use Livewire\Component;

class PreviewTypeExam extends Component
{
    public Exam $exam;

    public string $type;

    public Collection $activities;

    public string $title;

    public ?string $allocatedTime = null;

    private const TYPE_CONFIG = [
        'reading' => [
            'title' => 'Reading Comprehension <span>فهم المقروء</span>',
            'activities' => 'reading_activities',
            'time' => 'reading_time',
        ],
        'listening' => [
            'title' => 'Listening Comprehension <span>فهم المسموع</span>',
            'activities' => 'listening_activities',
            'time' => 'listening_time',
        ],
        'writing' => [
            'title' => 'Writing Expression <span>التعبير الكتابي</span>',
            'activities' => 'writing_activities',
            'time' => 'writing_time',
        ],
        'speaking' => [
            'title' => 'Speaking skills <span>مهارة التحدث</span>',
            'activities' => 'speaking_activities',
            'time' => 'speaking_time',
        ],
        'sentences_structures' => [
            'title' => 'Sentences Structures Comprehension <span>فهم بنية الجمل</span>',
            'activities' => 'sentences_structures_activities',
            'time' => 'sentences_structures_time',
        ],
    ];

    public function mount(Exam $exam, string $type)
    {
        if (! isset(self::TYPE_CONFIG[$type])) {
            abort(404);
        }

        $config = self::TYPE_CONFIG[$type];
        $this->type = $type;
        $this->title = $config['title'];
        $this->exam = $exam->load(['School', 'Grade']);

        $activityIds = $this->exam->{$config['activities']} ?? [];
        if (! is_array($activityIds) || count($activityIds) === 0) {
            return redirect()
                ->route('admin.exam-preview-types', ['exam' => $exam->id])
                ->with('error', 'No activities assigned for this exam type.');
        }

        $this->activities = Activity::with('Question')
            ->findMany($activityIds)
            ->sortBy(fn ($activity) => array_search($activity->id, $activityIds));

        $this->allocatedTime = $this->exam->{$config['time']} ?? null;
    }

    public function render()
    {
        return view('livewire.admin.exams.preview.preview-type-exam')
            ->layout('layouts.app')
            ->layoutData([
                'title' => 'Preview Exam',
            ]);
    }
}
