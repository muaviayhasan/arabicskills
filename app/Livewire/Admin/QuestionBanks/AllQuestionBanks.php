<?php

namespace App\Livewire\Admin\QuestionBanks;

use App\Livewire\Concerns\RestrictsToAdminSchool;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\Activity;
use App\Models\Grade;
use App\Models\Level;
use App\Models\Question;
use App\Models\School;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AllQuestionBanks extends Component
{
    use RestrictsToAdminSchool;
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $search;

    public $type;

    public $term;

    public $level_id;

    public $school_id;

    public $grade_id;

    protected $queryString = [
        'search' => ['except' => ''],
        'type' => ['except' => ''],
        'term' => ['except' => ''],
        'level_id' => ['except' => ''],
        'school_id' => ['except' => ''],
        'grade_id' => ['except' => ''],
    ];

    protected $listeners = [
        'activityDelete',
    ];

    // Duplicate term UI / state
    public $duplicateSourceTerm;

    public $duplicateTargetTerm;

    public $duplicateMode = 'skip';

    public $selectedDuplicateTypes = [];

    public $duplicatePreviewActivities = 0;

    public $duplicatePreviewQuestions = 0;

    public $duplicatePreviewStats = [];

    public $duplicateProcessing = false;

    public $duplicateActivityId = null; // optional: single activity copy

    public $duplicateErrors = [];

    public function mount($grade_id = null)
    {
        $adminSchoolId = $this->currentAdminSchoolId();
        if ($adminSchoolId !== null) {
            $this->school_id = $adminSchoolId;
        }

        if ($grade_id) {
            $this->grade_id = $grade_id;
        }
    }

    public function updatedSchoolId()
    {
        $this->grade_id = '';
        $this->resetPage();
    }

    public function updatedGradeId()
    {
        $this->resetPage();
    }

    public function manageSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        if ($this->currentAdminSchoolId() === null) {
            $this->school_id = '';
        }

        $this->search = '';
        $this->type = '';
        $this->term = '';
        $this->level_id = '';
        $this->grade_id = '';
        $this->resetPage();
    }

    protected function schoolGradeIds(): array
    {
        if (empty($this->school_id)) {
            return [];
        }

        return Section::query()
            ->where('school_id', $this->school_id)
            ->distinct()
            ->pluck('grade_id')
            ->filter()
            ->values()
            ->all();
    }

    public function deleteActivity($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'School record will be deleted permanently.',
            id: $id,
            emitBack: 'activityDelete',
        );
    }

    public function activityDelete($id)
    {
        $act = Activity::find($id);
        delete_image($act->image);
        $act->delete();
        $this->dispatch(
            'swal:toast',
            title: 'Assessments deleted successfully.',
            icon: 'success',
        );
    }

    /**
     * Open the Duplicate Term modal and initialize state.
     *
     * @param  int|null  $activityId
     */
    public function openDuplicateModal($activityId = null)
    {
        $this->duplicateActivityId = $activityId;
        $this->duplicateSourceTerm = null;
        $this->duplicateTargetTerm = null;
        $this->duplicateMode = 'skip';
        $this->selectedDuplicateTypes = [];
        $this->duplicatePreviewActivities = 0;
        $this->duplicatePreviewQuestions = 0;
        $this->duplicatePreviewStats = [];
        $this->duplicateErrors = [];

        if ($activityId) {
            $act = Activity::find($activityId);
            if ($act) {
                $this->duplicateSourceTerm = $act->term;
                $this->selectedDuplicateTypes = [$act->type];
                // compute preview now
                $this->updatedDuplicateSourceTerm($act->term);
            }
        }

        $this->dispatch('openDuplicateTermModal');
    }

    public function updatedSelectedDuplicateTypes($value)
    {
        $this->refreshDuplicatePreviewStats();
    }

    public function updatedDuplicateTargetTerm($value)
    {
        $this->refreshDuplicatePreviewStats();
    }

    public function updatedDuplicateMode($value)
    {
        $this->refreshDuplicatePreviewStats();
    }

    public function updatedDuplicateSourceTerm($value)
    {
        if ($value && $this->duplicateTargetTerm === $value) {
            $this->duplicateTargetTerm = null;
        }

        $this->refreshDuplicatePreviewStats();
    }

    protected function refreshDuplicatePreviewStats()
    {
        $this->duplicatePreviewActivities = 0;
        $this->duplicatePreviewQuestions = 0;
        $this->duplicatePreviewStats = [
            'total_types' => 0,
            'copiable_activities' => 0,
            'existing_activities' => 0,
            'overwritten_activities' => 0,
            'processed_activities' => 0,
            'copiable_questions' => 0,
            'processed_questions' => 0,
            'by_type' => [],
        ];

        if (! $this->duplicateSourceTerm) {
            return;
        }

        $q = Activity::where('term', $this->duplicateSourceTerm);
        if ($this->duplicateActivityId) {
            $q->where('id', $this->duplicateActivityId);
        }
        if (! empty($this->selectedDuplicateTypes)) {
            $q->whereIn('type', $this->selectedDuplicateTypes);
        }

        $activities = $q->get();
        $this->duplicatePreviewActivities = $activities->count();

        if ($activities->isEmpty()) {
            return;
        }

        $questionsByActivity = Question::whereIn('activity_id', $activities->pluck('id'))
            ->get()
            ->groupBy('activity_id');

        $this->duplicatePreviewQuestions = $questionsByActivity->flatten(1)->count();

        $groupedActivities = $activities->groupBy('type');

        foreach ($groupedActivities as $type => $typeActivities) {
            $typeQuestionCount = $typeActivities->sum(function ($activity) use ($questionsByActivity) {
                return $questionsByActivity->get($activity->id)?->count() ?? 0;
            });

            $existingCount = 0;
            $copiableActivities = $typeActivities;

            if ($this->duplicateTargetTerm) {
                $copiableActivities = $typeActivities->reject(function ($activity) {
                    return Activity::where('title', $activity->title)
                        ->where('term', $this->duplicateTargetTerm)
                        ->where('level_id', $activity->level_id)
                        ->where('grade_id', $activity->grade_id)
                        ->where('type', $activity->type)
                        ->exists();
                });

                $existingCount = $typeActivities->count() - $copiableActivities->count();
            }

            $copiableCount = $typeActivities->count() - $existingCount;
            $copiableQuestionCount = $copiableActivities->sum(function ($activity) use ($questionsByActivity) {
                return $questionsByActivity->get($activity->id)?->count() ?? 0;
            });

            $overwrittenCount = $this->duplicateMode === 'overwrite' ? $existingCount : 0;
            $processedActivitiesCount = $this->duplicateMode === 'overwrite' ? $typeActivities->count() : $copiableCount;
            $processedQuestionCount = $this->duplicateMode === 'overwrite' ? $typeQuestionCount : $copiableQuestionCount;

            $this->duplicatePreviewStats['by_type'][$type] = [
                'activities' => $typeActivities->count(),
                'questions' => $typeQuestionCount,
                'existing_activities' => $existingCount,
                'copiable_activities' => $copiableCount,
                'copiable_questions' => $copiableQuestionCount,
                'overwritten_activities' => $overwrittenCount,
                'processed_activities' => $processedActivitiesCount,
                'processed_questions' => $processedQuestionCount,
            ];

            $this->duplicatePreviewStats['existing_activities'] += $existingCount;
            $this->duplicatePreviewStats['copiable_activities'] += $copiableCount;
            $this->duplicatePreviewStats['copiable_questions'] += $copiableQuestionCount;
            $this->duplicatePreviewStats['overwritten_activities'] += $overwrittenCount;
            $this->duplicatePreviewStats['processed_activities'] += $processedActivitiesCount;
            $this->duplicatePreviewStats['processed_questions'] += $processedQuestionCount;
        }

        $this->duplicatePreviewStats['total_types'] = count($this->duplicatePreviewStats['by_type']);
    }

    public function duplicateQuestions()
    {
        $this->duplicateErrors = [];

        if (! $this->duplicateSourceTerm || ! $this->duplicateTargetTerm) {
            $this->duplicateErrors[] = 'Please select both source and target terms.';

            return;
        }

        if ($this->duplicateSourceTerm === $this->duplicateTargetTerm) {
            $this->duplicateErrors[] = 'Source and target terms must be different.';

            return;
        }

        $sourceQuery = Activity::where('term', $this->duplicateSourceTerm);
        if ($this->duplicateActivityId) {
            $sourceQuery->where('id', $this->duplicateActivityId);
        }
        if (! empty($this->selectedDuplicateTypes)) {
            $sourceQuery->whereIn('type', $this->selectedDuplicateTypes);
        }

        $sourceActivities = $sourceQuery->get();

        if ($sourceActivities->isEmpty()) {
            $this->dispatch('swal:toast', title: 'No activities to copy.', icon: 'info');

            return;
        }

        $this->duplicateProcessing = true;
        $copied = $skipped = $overwritten = $questionsCopied = 0;

        DB::transaction(function () use ($sourceActivities, &$copied, &$skipped, &$overwritten, &$questionsCopied) {
            foreach ($sourceActivities as $src) {
                $existing = Activity::where('title', $src->title)
                    ->where('term', $this->duplicateTargetTerm)
                    ->where('level_id', $src->level_id)
                    ->where('grade_id', $src->grade_id)
                    ->where('type', $src->type)
                    ->first();

                if ($existing) {
                    if ($this->duplicateMode === 'skip') {
                        $skipped++;

                        continue;
                    }

                    $existing->update([
                        'level_id' => $src->level_id,
                        'grade_id' => $src->grade_id,
                        'title' => $src->title,
                        'activity' => $src->activity,
                        'image' => $src->image,
                        'type' => $src->type,
                        'lang' => $src->lang ?? 'english',
                    ]);

                    $existing->Question()->delete();
                    $newActivityId = $existing->id;
                    $overwritten++;
                } else {
                    $new = Activity::create([
                        'level_id' => $src->level_id,
                        'grade_id' => $src->grade_id,
                        'term' => $this->duplicateTargetTerm,
                        'title' => $src->title,
                        'activity' => $src->activity,
                        'image' => $src->image,
                        'type' => $src->type,
                        'lang' => $src->lang ?? 'english',
                    ]);
                    $newActivityId = $new->id;
                    $copied++;
                }

                // copy questions for this activity
                $questions = $src->Question()->get();
                foreach ($questions as $q) {
                    Question::create([
                        'activity_id' => $newActivityId,
                        'question' => $q->question,
                        'options' => is_array($q->options) ? serialize($q->options) : serialize([]),
                        'correct_answer' => $q->correct_answer,
                        'image' => $q->image,
                        'type' => $q->type,
                    ]);

                    $questionsCopied++;
                }
            }
        });

        $this->duplicateProcessing = false;
        $this->refreshDuplicatePreviewStats();

        $this->dispatch('duplicateTermComplete', copied: $copied, skipped: $skipped, overwritten: $overwritten, questionsCopied: $questionsCopied);
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'type' => 'activities.type',
            // grades/levels sort on `number` so Year 2 precedes Year 10.
            'grade' => 'grades.number',
            'level' => 'levels.number',
            'assessment' => 'activities.title',
        ];
    }

    protected function applySortJoins($query)
    {
        $query->select('activities.*');

        return match ($this->sortField) {
            'grade' => $query->leftJoin('grades', 'grades.id', '=', 'activities.grade_id'),
            'level' => $query->leftJoin('levels', 'levels.id', '=', 'activities.level_id'),
            default => $query,
        };
    }

    public function render()
    {
        $activities = Activity::query()
            ->with(['assignedLevel', 'Grade'])
            ->when($this->school_id, function ($query) {
                $gradeIds = $this->schoolGradeIds();

                if (empty($gradeIds)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('grade_id', $gradeIds);
            })
            ->when($this->grade_id, fn ($query) => $query->where('grade_id', $this->grade_id))
            ->when($this->level_id, fn ($query) => $query->where('level_id', $this->level_id))
            ->when($this->term, fn ($query) => $query->where('term', $this->term))
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'LIKE', "%{$this->search}%")
                        ->orWhere('activity', 'LIKE', "%{$this->search}%");
                });
            })
            ->tap(fn ($query) => $this->applySorting($query, 'activities.created_at', 'desc'))
            ->paginate(10);

        $levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $types = Activity::select('type')
            ->distinct()
            ->orderByRaw('TRIM(type) ASC')
            ->pluck('type');

        $terms = unserialize(config('options.terms')) ?: [];

        $adminSchoolId = $this->currentAdminSchoolId();

        $schools = School::select('id', 'name')
            ->when($adminSchoolId !== null, fn ($query) => $query->whereKey($adminSchoolId))
            ->orderBy('name')
            ->get();

        $grades = Grade::query()
            ->select('grades.id', 'grades.name', 'grades.number')
            ->whereNotNull('grades.number')
            ->when($this->school_id, function ($query) {
                $query->whereIn('grades.id', function ($sub) {
                    $sub->select('grade_id')
                        ->from('sections')
                        ->where('school_id', $this->school_id)
                        ->distinct();
                });
            })
            ->orderBy('grades.number')
            ->get();

        return view('livewire.admin.question-banks.all-question-banks', [
            'activities' => $activities,
            'levels' => $levels,
            'types' => $types,
            'terms' => $terms,
            'schools' => $schools,
            'grades' => $grades,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Question Banks',
            'pageTitle' => 'Question Banks',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Question Banks' => '#',
            ],
        ]);
    }
}
