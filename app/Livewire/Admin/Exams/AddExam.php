<?php

namespace App\Livewire\Admin\Exams;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Support\ExamActivityQuery;
use App\Support\ExamLevelHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddExam extends Component
{
    public $inputs = [];

    public $schools;

    public $grades;

    public $levels;

    public $createForAll = false;

    protected $listeners = [
        'updateExamLevelIds',
    ];

    private const ACTIVITY_TYPES = [
        'reading',
        'listening',
        'writing',
        'speaking',
        'sentences_structures',
    ];

    public function mount()
    {
        $this->inputs['school_id'] = null;
        $this->inputs['level_ids'] = [];
        $this->schools = School::orderBy('name')->get()->sortBy('name', SORT_NATURAL);
        $this->levels = Level::query()->orderBy('number')->get(['id', 'name', 'number']);
        $this->grades = collect();
    }

    public function updatedInputsSchoolId(): void
    {
        $this->inputs['grade_id'] = null;
        $this->loadGradesForSchool();
    }

    public function updatedCreateForAll(): void
    {
        if ($this->createForAll) {
            $this->inputs['grade_id'] = null;
        }
    }

    public function updateExamLevelIds($levelIds): void
    {
        $this->inputs['level_ids'] = ExamLevelHelper::normalizeLevelIds(is_array($levelIds) ? $levelIds : []);
    }

    protected function loadGradesForSchool(): void
    {
        $schoolId = (int) ($this->inputs['school_id'] ?? 0);

        if ($schoolId === 0) {
            $this->grades = collect();

            return;
        }

        $gradeIds = Section::query()
            ->where('school_id', $schoolId)
            ->distinct()
            ->pluck('grade_id');

        $this->grades = Grade::query()
            ->whereIn('id', $gradeIds)
            ->whereNotNull('number')
            ->orderBy('number')
            ->get(['id', 'name', 'number']);
    }

    public function addExam()
    {
        $rules = [
            'inputs.school_id' => 'required|exists:schools,id',
            'inputs.reading_time' => 'required|date_format:H:i',
            'inputs.listening_time' => 'required|date_format:H:i',
            'inputs.writing_time' => 'required|date_format:H:i',
            'inputs.speaking_time' => 'required|date_format:H:i',
            'inputs.sentences_structures_time' => 'required|date_format:H:i',
            'inputs.term' => 'required',
        ];

        if (! $this->createForAll) {
            $rules['inputs.grade_id'] = [
                'required',
                Rule::exists('grades', 'id')->whereNotNull('number'),
            ];
        }

        $rules['inputs.level_ids'] = 'required|array|min:1';
        $rules['inputs.level_ids.*'] = 'exists:levels,id';

        $this->validate($rules);

        try {
            DB::beginTransaction();

            $levelIds = ExamLevelHelper::normalizeLevelIds($this->inputs['level_ids'] ?? []);

            if ($levelIds === []) {
                throw new Exception('Select at least one exam level.');
            }

            $examsCreated = 0;

            if ($this->createForAll) {
                $this->loadGradesForSchool();

                $grades = $this->grades->filter(function ($grade) use ($levelIds) {
                    return ExamActivityQuery::hasForPlacement(
                        (int) $grade->id,
                        $levelIds,
                        $this->inputs['term']
                    );
                })->values();

                if ($grades->isEmpty()) {
                    throw new Exception('No grades in this school have question bank activities for the selected term and levels.');
                }

                foreach ($grades as $grade) {
                    if (Exam::existsForGradeTerm(
                        (int) $this->inputs['school_id'],
                        (int) $grade->id,
                        $this->inputs['term']
                    )) {
                        continue;
                    }

                    $exam = $this->createExamRecord((int) $grade->id, $levelIds);
                    $examsCreated++;

                    ExamActivityQuery::assignAllTypesToExam($exam, self::ACTIVITY_TYPES);
                    $exam->syncStudentExams();
                }

                if ($examsCreated === 0) {
                    throw new Exception('All matching grades already have exams for the selected term.');
                }
            } else {
                if (! ExamActivityQuery::hasForPlacement(
                    (int) $this->inputs['grade_id'],
                    $levelIds,
                    $this->inputs['term']
                )) {
                    throw new Exception('No question bank activities found for the selected grade, levels, and term.');
                }

                if (Exam::existsForGradeTerm(
                    (int) $this->inputs['school_id'],
                    (int) $this->inputs['grade_id'],
                    $this->inputs['term']
                )) {
                    throw new Exception('An exam already exists for this school, grade, and term.');
                }

                $exam = $this->createExamRecord((int) $this->inputs['grade_id'], $levelIds);
                $examsCreated = 1;

                ExamActivityQuery::assignAllTypesToExam($exam, self::ACTIVITY_TYPES);
                $exam->syncStudentExams();
            }

            DB::commit();

            $message = $examsCreated > 1
                ? "{$examsCreated} Exams added successfully"
                : 'New Exam added successfully';

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: $message,
                url: route('admin.exams'),
            );
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error adding exam: '.$e->getMessage(), [
                'school_id' => $this->inputs['school_id'] ?? null,
                'grade_id' => $this->inputs['grade_id'] ?? null,
                'level_ids' => $this->inputs['level_ids'] ?? [],
                'stack' => $e->getTraceAsString(),
            ]);

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    /**
     * @param  list<int>  $levelIds
     */
    protected function createExamRecord(int $gradeId, array $levelIds): Exam
    {
        return Exam::create([
            'school_id' => $this->inputs['school_id'],
            'grade_id' => $gradeId,
            'section_id' => null,
            'term' => $this->inputs['term'],
            'status' => 'pending',
            'level_ids' => $levelIds,
            'reading_time' => $this->inputs['reading_time'],
            'listening_time' => $this->inputs['listening_time'],
            'writing_time' => $this->inputs['writing_time'],
            'speaking_time' => $this->inputs['speaking_time'],
            'sentences_structures_time' => $this->inputs['sentences_structures_time'],
        ]);
    }

    public function render()
    {
        return view('livewire.admin.exams.add-exam')->layout('layouts.base')->layoutData([
            'title' => 'Add Exam',
            'pageTitle' => 'Add Exam',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => route('admin.exams'),
                'Add Exam' => '#',
            ],
        ]);
    }
}
