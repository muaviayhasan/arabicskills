<?php

namespace App\Livewire\Admin\Exams;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\StudentExam;
use App\Support\ExamLevelHelper;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditExam extends Component
{
    public $inputs = [];

    public $schools;

    public $grades;

    public $levels;

    public $exam;

    protected $listeners = [
        'updateExamLevelIds',
    ];

    public function mount(Exam $exam)
    {
        if ($exam->trashed()) {
            return $this->redirect(route('admin.exams'));
        }

        $this->exam = $exam;
        $this->inputs = $exam->toArray();
        $this->inputs['level_ids'] = $exam->normalizedLevelIds();

        $this->levels = Level::query()->orderBy('number')->get(['id', 'name', 'number']);
        $this->schools = School::all()->sortBy('name', SORT_NATURAL);
        $this->loadGradesForSchool();

        $this->dispatch('exam-level-ids-sync', levelIds: $this->inputs['level_ids']);
    }

    public function updatedInputsSchoolId(): void
    {
        $this->loadGradesForSchool();
    }

    public function updateExamLevelIds($levelIds): void
    {
        $this->inputs['level_ids'] = ExamLevelHelper::normalizeLevelIds(is_array($levelIds) ? $levelIds : []);
    }

    protected function loadGradesForSchool(): void
    {
        $schoolId = (int) ($this->inputs['school_id'] ?? 0);

        if ($schoolId === 0) {
            $this->grades = Grade::query()
                ->whereNotNull('number')
                ->orderBy('number')
                ->get(['id', 'name', 'number']);

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

    public function editExam()
    {
        $this->validate([
            'inputs.grade_id' => [
                'required',
                Rule::exists('grades', 'id')->whereNotNull('number'),
            ],
            'inputs.school_id' => 'required|exists:schools,id',
            'inputs.reading_time' => 'required|date_format:H:i',
            'inputs.sentences_structures_time' => 'required|date_format:H:i',
            'inputs.listening_time' => 'required|date_format:H:i',
            'inputs.writing_time' => 'required|date_format:H:i',
            'inputs.speaking_time' => 'required|date_format:H:i',
            'inputs.term' => 'required',
            'inputs.level_ids' => 'required|array|min:1',
            'inputs.level_ids.*' => 'exists:levels,id',
        ]);

        try {
            DB::beginTransaction();

            $levelIds = ExamLevelHelper::normalizeLevelIds($this->inputs['level_ids'] ?? []);

            if ($levelIds === []) {
                throw new Exception('Select at least one exam level.');
            }

            $this->inputs['level_ids'] = $levelIds;

            $previousLevelIds = $this->exam->normalizedLevelIds();
            sort($previousLevelIds);
            $newLevelIds = $levelIds;
            sort($newLevelIds);

            $placementChanged = (int) $this->exam->school_id !== (int) $this->inputs['school_id']
                || (int) $this->exam->grade_id !== (int) $this->inputs['grade_id']
                || $this->exam->term !== $this->inputs['term']
                || $previousLevelIds !== $newLevelIds;

            if ($placementChanged && Exam::existsForGradeTerm(
                (int) $this->inputs['school_id'],
                (int) $this->inputs['grade_id'],
                $this->inputs['term'],
                null,
                $this->exam->id
            )) {
                throw new Exception('An exam already exists for this school, grade, and term.');
            }

            $this->inputs['section_id'] = $this->exam->section_id;

            $this->exam->update($this->inputs);
            $this->exam->refresh();

            $this->exam->syncStudentExams();

            $eligibleStudentIds = $this->exam->eligibleStudentsQuery()->pluck('id');

            StudentExam::where('exam_id', $this->exam->id)
                ->whereNotIn('student_id', $eligibleStudentIds)
                ->delete();

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Exam updated successfully',
            );
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error updating exam: '.$e->getMessage(), [
                'exam_id' => $this->exam->id ?? null,
                'stack' => $e->getTraceAsString(),
            ]);

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view('livewire.admin.exams.edit-exam')->layout('layouts.base')->layoutData([
            'title' => 'Edit Exam',
            'pageTitle' => 'Edit Exam',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => route('admin.exams'),
                'Edit Exam' => '#',
            ],
        ]);
    }
}
