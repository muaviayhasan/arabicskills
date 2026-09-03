<?php

namespace App\Livewire\Admin\Exams;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Support\ExamActivityQuery;
use App\Support\ExamLevelHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class BulkCreateExam extends Component
{
    public array $inputs = [
        'school_id' => null,
        'grade_id' => null,
        'level_id' => null,
        'term' => null,
        'activity_types' => [],
        'reading_time' => null,
        'listening_time' => null,
        'writing_time' => null,
        'speaking_time' => null,
        'sentences_structures_time' => null,
    ];

    public array $preview = [];

    public $schools;

    public $grades;

    public $levels;

    public array $availableTypes = ['reading', 'listening', 'writing', 'speaking', 'sentences_structures'];

    protected array $reusableStatuses = ['pending', 'active'];

    public function mount(): void
    {
        $this->schools = School::orderBy('name')->get()->sortBy('name', SORT_NATURAL);
        $this->grades = Grade::query()
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);
        $this->levels = Level::query()->orderBy('number')->get(['id', 'name', 'number']);
    }

    public function updatedInputs($value, $key): void
    {
        $this->preview = [];
    }

    /**
     * @return list<int>
     */
    protected function resolveBulkLevelIds(): array
    {
        if (! empty($this->inputs['level_id'])) {
            return [(int) $this->inputs['level_id']];
        }

        return ExamLevelHelper::allLevelIds();
    }

    public function loadPreview(): void
    {
        $this->validate([
            'inputs.school_id' => 'required|exists:schools,id',
            'inputs.term' => 'required|string',
            'inputs.activity_types' => 'required|array|min:1',
            'inputs.activity_types.*' => 'in:'.implode(',', $this->availableTypes),
            'inputs.grade_id' => ['nullable', Rule::exists('grades', 'id')->whereNotNull('number')],
            'inputs.level_id' => 'nullable|exists:levels,id',
        ], [], [
            'inputs.school_id' => 'school',
            'inputs.term' => 'term',
            'inputs.activity_types' => 'assessments',
            'inputs.grade_id' => 'grade',
            'inputs.level_id' => 'level',
        ]);

        $this->preview = [];
        $levelIds = $this->resolveBulkLevelIds();
        $levelLabel = ExamLevelHelper::levelNamesLabel($levelIds);

        $gradeIds = Section::query()
            ->where('school_id', $this->inputs['school_id'])
            ->distinct()
            ->pluck('grade_id');

        $gradesQuery = Grade::query()
            ->whereIn('id', $gradeIds)
            ->whereNotNull('number');

        if (! empty($this->inputs['grade_id'])) {
            $gradesQuery->where('id', $this->inputs['grade_id']);
        }

        $grades = $gradesQuery->orderBy('number')->get()->unique('id')->values();

        if ($grades->isEmpty()) {
            $this->dispatch('swal:alert', icon: 'info', title: 'No grades found', text: 'No matching grades were found for the selected school.');

            return;
        }

        foreach ($grades as $grade) {
            $bankByType = [];

            foreach ($this->inputs['activity_types'] as $type) {
                $bankByType[$type] = ExamActivityQuery::hasForPlacement(
                    (int) $grade->id,
                    $levelIds,
                    $this->inputs['term'],
                    $type
                );
            }

            $reusableExam = Exam::query()
                ->where('school_id', $this->inputs['school_id'])
                ->where('grade_id', $grade->id)
                ->where('term', $this->inputs['term'])
                ->whereNull('section_id')
                ->whereIn('status', $this->reusableStatuses)
                ->orderByDesc('created_at')
                ->first();

            $gradeExamExists = Exam::existsForGradeTerm(
                (int) $this->inputs['school_id'],
                (int) $grade->id,
                $this->inputs['term']
            );

            $studentQuery = Student::query()
                ->where('school_id', $this->inputs['school_id'])
                ->where('grade_id', $grade->id);

            if ($levelIds !== []) {
                $studentQuery->whereIn('level_id', $levelIds);
            }

            $studentCount = $studentQuery->count();

            $existingTypes = [];
            $missingTypes = [];
            $skippedTypes = [];

            foreach ($this->inputs['activity_types'] as $type) {
                if (! $bankByType[$type]) {
                    $skippedTypes[] = $type;

                    continue;
                }

                if ($reusableExam) {
                    $current = $reusableExam->{$type.'_activities'} ?: [];

                    if (! empty($current)) {
                        $existingTypes[] = $type;
                    } else {
                        $missingTypes[] = $type;
                    }
                } else {
                    $missingTypes[] = $type;
                }
            }

            if ($gradeExamExists && ! $reusableExam) {
                $action = 'skip';
                $reason = 'An exam already exists for this grade and term.';
            } elseif (empty($missingTypes) && ! empty($skippedTypes) && empty($existingTypes) && ! $reusableExam) {
                $action = 'skip';
                $reason = 'No question bank activities for this grade/levels/term/type.';
            } elseif ($reusableExam && empty($missingTypes)) {
                $action = 'skip';
                $reason = 'All selected assessments already exist in current '.$reusableExam->status.' exam.';
            } elseif ($reusableExam) {
                $action = 'update';
                $reason = 'Append missing assessments to existing '.$reusableExam->status.' exam.';
            } else {
                $action = 'create';
                $reason = 'Create new exam.';
            }

            $this->preview[] = [
                'grade_id' => $grade->id,
                'grade_name' => $grade->name,
                'level_label' => $levelLabel,
                'level_ids' => $levelIds,
                'student_count' => $studentCount,
                'existing_exam' => $reusableExam?->id,
                'existing_status' => $reusableExam?->status,
                'existing_types' => $existingTypes,
                'missing_types' => $missingTypes,
                'skipped_types' => $skippedTypes,
                'action' => $action,
                'reason' => $reason,
            ];
        }
    }

    public function createBulkExams(): void
    {
        if (empty($this->preview)) {
            $this->dispatch('swal:alert', icon: 'warning', title: 'Load preview first');

            return;
        }

        $rules = [
            'inputs.school_id' => 'required|exists:schools,id',
            'inputs.term' => 'required|string',
            'inputs.activity_types' => 'required|array|min:1',
        ];

        $messages = [];
        $attributes = [];
        $typeLabels = [
            'reading' => 'Reading',
            'listening' => 'Listening',
            'writing' => 'Writing',
            'speaking' => 'Speaking',
            'sentences_structures' => 'Sentences & Structures',
        ];

        foreach ($this->inputs['activity_types'] as $type) {
            $key = 'inputs.'.$type.'_time';
            $rules[$key] = ['required', 'date_format:H:i', 'after:00:05'];
            $attributes[$key] = ($typeLabels[$type] ?? $type).' time';
            $messages[$key.'.after'] = 'The :attribute must be greater than 00:05.';
        }

        $this->validate($rules, $messages, $attributes);

        try {
            DB::beginTransaction();

            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($this->preview as $row) {
                if ($row['action'] === 'skip') {
                    $skipped++;

                    continue;
                }

                $grade = Grade::find($row['grade_id']);

                if (! $grade) {
                    continue;
                }

                $levelIds = ExamLevelHelper::normalizeLevelIds($row['level_ids'] ?? []);

                if ($row['action'] === 'update' && $row['existing_exam']) {
                    $exam = Exam::find($row['existing_exam']);

                    if (! $exam || ! in_array($exam->status, $this->reusableStatuses, true)) {
                        continue;
                    }

                    foreach ($row['missing_types'] as $type) {
                        $activities = ExamActivityQuery::baseQuery($exam, $type)->get();
                        $exam->{$type.'_activities'} = $activities->pluck('id')->values()->all();
                        $exam->{$type.'_time'} = $this->inputs[$type.'_time'];
                    }

                    $exam->save();
                    $exam->syncStudentExams();
                    $updated++;

                    continue;
                }

                if (Exam::existsForGradeTerm(
                    (int) $this->inputs['school_id'],
                    (int) $grade->id,
                    $this->inputs['term']
                )) {
                    $skipped++;

                    continue;
                }

                $examData = [
                    'school_id' => $this->inputs['school_id'],
                    'grade_id' => $grade->id,
                    'section_id' => null,
                    'level_ids' => $levelIds,
                    'term' => $this->inputs['term'],
                    'status' => 'pending',
                ];

                foreach ($this->inputs['activity_types'] as $type) {
                    if (in_array($type, $row['skipped_types'], true)) {
                        continue;
                    }

                    $examData[$type.'_time'] = $this->inputs[$type.'_time'];
                }

                $exam = Exam::create($examData);

                foreach ($this->inputs['activity_types'] as $type) {
                    if (in_array($type, $row['skipped_types'], true)) {
                        continue;
                    }

                    $activities = ExamActivityQuery::baseQuery($exam, $type)->get();
                    $exam->{$type.'_activities'} = $activities->pluck('id')->values()->all();
                }

                $exam->save();
                $exam->syncStudentExams();
                $created++;
            }

            DB::commit();

            $parts = [];

            if ($created > 0) {
                $parts[] = "{$created} created";
            }

            if ($updated > 0) {
                $parts[] = "{$updated} updated";
            }

            if ($skipped > 0) {
                $parts[] = "{$skipped} skipped";
            }

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Bulk exams processed',
                text: implode(', ', $parts) ?: 'No changes made',
                url: route('admin.exams'),
            );
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Bulk create exam failed: '.$e->getMessage(), [
                'school_id' => $this->inputs['school_id'] ?? null,
                'term' => $this->inputs['term'] ?? null,
                'stack' => $e->getTraceAsString(),
            ]);

            $this->dispatch('swal:alert', icon: 'error', text: $e->getMessage());
        }
    }

    public function resetForm(): void
    {
        $this->inputs = [
            'school_id' => null,
            'grade_id' => null,
            'level_id' => null,
            'term' => null,
            'activity_types' => [],
            'reading_time' => null,
            'listening_time' => null,
            'writing_time' => null,
            'speaking_time' => null,
            'sentences_structures_time' => null,
        ];
        $this->preview = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.exams.bulk-create-exam');
    }
}
