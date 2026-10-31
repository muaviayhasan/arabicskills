<?php

namespace App\Livewire\Student\Exams;

use App\Livewire\Student\Concerns\LoadsStudentRelations;
use App\Models\Exam;
use App\Models\Question;
use App\Models\StudentExam;
use App\Models\TakeExam;
use App\Support\ExamActivityQuery;
use Livewire\Component;

class SkillExams extends Component
{
    use LoadsStudentRelations;

    public $latestExam;

    public $expiredExams = [];

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

    public function mount()
    {
        $std = $this->authenticatedStudent();

        Exam::reconcileStudentExams($std);

        $visibleExams = Exam::resolvedExamsForStudent($std, Exam::STUDENT_VISIBLE_STATUSES);
        $resolvedExamIds = $visibleExams->pluck('id')->all();

        $this->latestExam = empty($resolvedExamIds)
            ? null
            : StudentExam::where('student_id', $std->id)
                ->whereIn('exam_id', $resolvedExamIds)
                ->where('checked', false)
                ->with(['Exam.Grade', 'Exam.Section', 'Exam.School'])
                ->get()
                ->sortByDesc(fn ($row) => $row->Exam?->created_at)
                ->first();

        if (! $this->latestExam) {
            $expiredExamIds = Exam::resolvedExamsForStudent($std, ['expired'])->pluck('id');

            $this->expiredExams = StudentExam::where('student_id', $std->id)
                ->when($expiredExamIds->isNotEmpty(), fn ($q) => $q->whereIn('exam_id', $expiredExamIds))
                ->when($expiredExamIds->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
                ->with(['Exam.Grade', 'Exam.Section', 'Exam.School'])
                ->latest()
                ->get();
        }

    }

    public function attempt($type, $status)
    {
        if ($this->latestExam->Exam->status == 'pending') {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Exam is not active yet.'
            );
        } elseif ($this->latestExam->Exam->status == 'suspended') {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Exam is suspended.'
            );
        } elseif ($this->latestExam->Exam->status == 'expired') {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Exam is expired.'
            );
        } elseif ($this->latestExam->Exam->status == 'active' && $status != 'unattempted') {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'You have already attempted this exam.'
            );
        } else {
            return redirect()->route('student.exams-instructions', ['type' => $type]);
        }
    }

    /**
     * The activities this student is served per skill. An exam can cover
     * several levels, so the list stored on the exam is not the paper any one
     * student sits: a skill can be empty for their level even though the exam
     * has plenty of it for another.
     *
     * @return array<string, list<int>>
     */
    public function skillActivities(): array
    {
        $exam = $this->latestExam?->Exam;
        $student = $this->authenticatedStudent();

        $activities = [];

        foreach (array_keys($this->activityLabels) as $skill) {
            $activities[$skill] = $exam
                ? ExamActivityQuery::activityIdsForStudent($exam, $student, $skill)
                : [];
        }

        return $activities;
    }

    /**
     * The attempted skills that still have a question with nothing recorded
     * against them.
     *
     * The page used to work this out per skill card, loading every activity,
     * every question on it and every answer the student gave, just to decide
     * whether to print one warning line. It is two aggregate queries for the
     * whole page now, and nothing is hydrated.
     *
     * @param  array<string, list<int>>  $skillActivities
     * @return array<string, bool>
     */
    public function skillsWithMissingAnswers(array $skillActivities): array
    {
        $missing = [];
        $wanted = [];

        foreach ($skillActivities as $skill => $activityIds) {
            $missing[$skill] = false;

            if ($activityIds === [] || ($this->latestExam?->{$skill.'_status'}['status'] ?? null) !== 'attempted') {
                continue;
            }

            $wanted[$skill] = $activityIds;
        }

        $allIds = array_values(array_unique(array_merge(...array_values($wanted) ?: [[]])));

        if ($allIds === []) {
            return $missing;
        }

        $asked = Question::query()
            ->whereIn('activity_id', $allIds)
            ->groupBy('activity_id')
            ->selectRaw('activity_id, COUNT(*) as total')
            ->pluck('total', 'activity_id');

        $answered = TakeExam::query()
            ->join('questions', 'questions.id', '=', 'take_exams.question_id')
            ->where('take_exams.student_id', $this->latestExam->student_id)
            ->whereIn('questions.activity_id', $allIds)
            ->whereNotNull('take_exams.answer')
            ->whereNotIn('take_exams.answer', ['', '{}'])
            ->groupBy('questions.activity_id')
            ->selectRaw('questions.activity_id as activity_id, COUNT(DISTINCT take_exams.question_id) as answered')
            ->pluck('answered', 'activity_id');

        foreach ($wanted as $skill => $activityIds) {
            foreach ($activityIds as $activityId) {
                if ((int) ($asked[$activityId] ?? 0) > (int) ($answered[$activityId] ?? 0)) {
                    $missing[$skill] = true;
                    break;
                }
            }
        }

        return $missing;
    }

    public function render()
    {
        $student = $this->authenticatedStudent();
        $skillActivities = $this->skillActivities();

        return view('livewire.student.exams.skill-exams', [
            'skillActivities' => $skillActivities,
            'missingAnswers' => $this->skillsWithMissingAnswers($skillActivities),
            // The exam may be labelled "All Levels"; the student sits one.
            'studentLevelName' => $student?->assignedLevel?->name,
        ])->layout('layouts.app')->layoutData([
            'title' => 'Exams',
        ]);
    }
}
