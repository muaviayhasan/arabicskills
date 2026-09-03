<?php

namespace App\Livewire\Admin\ExamCheck;

use App\Exports\ResultExports;
use App\Livewire\Concerns\RestrictsToAdminSchool;
use App\Models\Level;
use App\Models\School;
use App\Models\StudentExam;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class AllResults extends Component
{
    use RestrictsToAdminSchool;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $searchWord;

    public $searchColumn;

    public $schools;

    public $searchSchool;

    public $year;

    public $archiveStatus = 'active';

    protected $listeners = [
        'manageSearch', 'uncheckMark',
    ];

    public function mount($school = null)
    {
        $adminSchoolId = $this->currentAdminSchoolId();

        if ($adminSchoolId !== null) {
            $this->searchSchool = $adminSchoolId;
            $this->schools = School::whereKey($adminSchoolId)->get();

            return;
        }

        $this->searchSchool = $school;
        $this->schools = School::orderBy('name')->get();
    }

    public function search()
    {
        $this->resetPage();
    }

    public function exportExcel()
    {
        return Excel::download(
            new ResultExports($this->searchColumn, $this->searchWord, $this->searchSchool, $this->year, $this->archiveStatus),
            'student_list_date_'.Carbon::now()->format('Y-m-d').'.xlsx'
        );
    }

    public function manageSearch($searchWord, $searchColumn)
    {
        $this->resetPage();
        $this->searchWord = $searchWord;
        $this->searchColumn = $searchColumn;
    }

    public function markUnchecked($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'If the exam marks are checked, they will be reappear on the attempted exams page. Are you sure?',
            id: $id,
            emitBack: 'uncheckMark',
        );
    }

    public function uncheckMark($id)
    {
        $exm = StudentExam::with('Result')->find($id);

        $exm->update([
            'checked' => false,
        ]);

        $this->dispatch(
            'swal:toast',
            title: 'Student exam marked as unchecked.',
            icon: 'success',
        );
    }

    public function render()
    {
        $adminSchoolId = $this->currentAdminSchoolId();
        if ($adminSchoolId !== null) {
            $this->searchSchool = $adminSchoolId;
        }

        $exams = StudentExam::when($this->searchSchool, function ($query) {
            $query->whereRelation('Exam', 'school_id', '=', $this->searchSchool);
        })
            ->whereHas('Student', function ($query) {
                $query->applyArchiveFilters($this->archiveStatus, $this->year);
            })
            ->whereHas('Exam', function ($query) {
                $query->applyArchiveFilters($this->archiveStatus);
            })
            ->when($this->searchColumn && $this->searchColumn == 'term', function ($query) {
                $query->whereRelation('Exam', 'term', 'LIKE', "%{$this->searchWord}%");
            })
            ->when($this->searchColumn && $this->searchColumn == 'grade', function ($query) {
                $query->whereRelation('Exam.Grade', 'name', 'LIKE', "%{$this->searchWord}%");
            })
            ->when($this->searchColumn && $this->searchColumn == 'section', function ($query) {
                $query->whereHas('Student.Section', function ($q) {
                    $q->where('name', 'LIKE', "%{$this->searchWord}%");
                });
            })
            ->when($this->searchColumn && $this->searchColumn == 'level', function ($query) {
                $matchingLevelIds = Level::query()
                    ->where('name', 'LIKE', "%{$this->searchWord}%")
                    ->pluck('id');

                if ($matchingLevelIds->isEmpty()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereHas('Exam', function ($q) use ($matchingLevelIds) {
                    $q->where(function ($sub) use ($matchingLevelIds) {
                        foreach ($matchingLevelIds as $levelId) {
                            $sub->orWhereJsonContains('level_ids', (int) $levelId);
                        }
                    });
                });
            })
            ->when($this->searchColumn && $this->searchColumn == 'student', function ($query) {
                $query->whereRelation('Student', 'registration', 'LIKE', "%{$this->searchWord}%");
            })

            ->where('checked', true)
            ->with(['Exam' => function ($quer) {
                $quer->applyArchiveFilters($this->archiveStatus)
                    ->select('id', 'term', 'school_id', 'grade_id', 'level_ids', 'deleted_at')
                    ->with('Grade:id,name')
                    ->with('School:id,name');
            }])
            ->with(['Student' => function ($quer) {
                $quer->applyArchiveFilters($this->archiveStatus, $this->year)
                    ->select('id', 'name', 'registration', 'section_id', 'deleted_at');
            }])
            ->with('Result')
            ->orderByDESC('created_at')
            ->paginate(20);

        return view('livewire.admin.exam-check.all-results', [
            'exams' => $exams,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Exam Results',
            'pageTitle' => 'Exam Results',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exam Results' => '#',
            ],
        ]);
    }
}
