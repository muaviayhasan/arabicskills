<?php

namespace App\Livewire\Admin\Exams\Preview;

use App\Models\Activity;
use App\Models\Exam;
use App\Support\ExamActivityQuery;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class PreviewTypeExam extends Component
{
    public Exam $exam;

    public string $type;

    public Collection $activities;

    public string $title;

    public ?string $allocatedTime = null;

    /**
     * The level being previewed, carried over from the skill list. An exam
     * built for several levels holds every level's activities in one list, so
     * without this the preview shows a paper no student ever sits.
     */
    #[Url(as: 'level', except: '')]
    public string $previewLevelId = '';

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

        $levelIds = $this->exam->normalizedLevelIds();

        if ($this->previewLevelId !== '' && ! in_array((int) $this->previewLevelId, $levelIds, true)) {
            $this->previewLevelId = '';
        }

        if ($this->previewLevelId === '' && count($levelIds) > 1) {
            $this->previewLevelId = (string) $levelIds[0];
        }

        $activityIds = ExamActivityQuery::activityIdsForLevel(
            $this->exam,
            $this->previewLevelId === '' ? null : (int) $this->previewLevelId,
            $type
        );

        if (count($activityIds) === 0) {
            return redirect()
                ->route('admin.exam-preview-types', ['exam' => $exam->id, 'level' => $this->previewLevelId ?: null])
                ->with('error', 'No activities assigned for this exam type at the selected level.');
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
