<?php

namespace App\Livewire\Admin\Exams\Preview;

use App\Models\Exam;
use App\Models\Level;
use App\Support\ExamActivityQuery;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

class PreviewTypes extends Component
{
    public Exam $exam;

    /**
     * Which level to preview. An exam can be built for several levels at once,
     * and each level is a paper of its own, so the preview shows one at a time
     * rather than all of them stacked together.
     */
    #[Url(as: 'level', except: '')]
    public string $previewLevelId = '';

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

        $levelIds = $this->exam->normalizedLevelIds();

        // Default to the first level, so a multi-level exam never opens on a
        // combined paper that no student will ever sit.
        if ($this->previewLevelId === '' && count($levelIds) > 1) {
            $this->previewLevelId = (string) $levelIds[0];
        }

        if ($this->previewLevelId !== '' && ! in_array((int) $this->previewLevelId, $levelIds, true)) {
            $this->previewLevelId = count($levelIds) > 1 ? (string) $levelIds[0] : '';
        }
    }

    /**
     * The levels this exam covers, in level order. Empty when there is only
     * one, as there is then nothing to choose between.
     */
    public function levels(): Collection
    {
        $levelIds = $this->exam->normalizedLevelIds();

        if (count($levelIds) < 2) {
            return collect();
        }

        return Level::query()->whereIn('id', $levelIds)->orderBy('number')->get();
    }

    /**
     * Activity ids per skill for the level being previewed — the same list a
     * student on that level is served.
     *
     * @return array<string, list<int>>
     */
    public function activityIdsByType(): array
    {
        $ids = [];

        foreach (array_keys($this->activityLabels) as $type) {
            $ids[$type] = ExamActivityQuery::activityIdsForLevel(
                $this->exam,
                $this->previewLevelId === '' ? null : (int) $this->previewLevelId,
                $type
            );
        }

        return $ids;
    }

    public function render()
    {
        return view('livewire.admin.exams.preview.preview-types', [
            'levels' => $this->levels(),
            'activityIdsByType' => $this->activityIdsByType(),
        ])
            ->layout('layouts.app')
            ->layoutData([
                'title' => 'Preview Exam',
            ]);
    }
}
