<?php

namespace App\Livewire\Admin\ExamCheck;

use App\Models\Exam;
use App\Models\Result;
use Livewire\Component;
use App\Models\Activity;
use App\Models\StudentExam;
use App\Support\ExamActivityQuery;
use App\Livewire\Concerns\RestrictsToAdminSchool;

class ShowPaper extends Component
{
    use RestrictsToAdminSchool;
    public $student_exam;
    public $result;
    public $activities;
    public $answers;
    public $type;
    public $marks;
    public $inputs = [];
    public function mount($type, $exam_id)
    {
        $this->type = $type;
        $this->student_exam = StudentExam::query()
            ->with('TakeExam')
            ->when($this->currentAdminSchoolId() !== null, function ($query) {
                $query->whereRelation('Exam', 'school_id', $this->currentAdminSchoolId());
            })
            ->find($exam_id);

        if (!$this->student_exam) {
            return redirect()->route('admin.attempted-exams');
        }
        $exam = Exam::find($this->student_exam->exam_id);

        // Mark only what this student was served: an exam built for several
        // levels holds every level's activities in one list.
        $acties = $exam
            ? ExamActivityQuery::activityIdsForStudent($exam, $this->student_exam->Student, $type)
            : [];

        if (!$exam || $acties == []) {
            return redirect()->route('admin.attempted-exams');
        }
        if (is_array($acties)) {
            foreach ($acties as $i => $act) {
                $this->activities[$i] = Activity::where('id', $act)->with(['Question' => function ($query) {
                    $query->with(['TakeExam' => function ($quer) {
                        $quer->where('student_exam_id', $this->student_exam->id);
                    }]);
                }])
                    ->first();
            }
        } else {
            return redirect()->route('admin.attempted-exams');
        }

        $this->activities = collect($this->activities);
        $this->result = Result::firstOrCreate([
            'student_exam_id' => $this->student_exam->id
        ]);

        $type = $this->type . "_marks";
        $this->inputs = $this->result->$type ?? [];
        $this->marks = collect($this->result->$type)->sum();
    }

    public function render()
    {
        return view('livewire.admin.exam-check.show-paper')->layout('layouts.base')->layoutData([
            'title' => 'Exams Marking',
            'pageTitle' => 'Exams Marking',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Attempted Exams' => route('admin.attempted-exams'),
                'Exams Marking' => "#",
            ],
        ]);
    }
    
}
