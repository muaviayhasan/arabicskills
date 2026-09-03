<?php

namespace App\Livewire\Admin\Exams;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Support\ExamActivityQuery;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AllExams extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $school_id;

    public $grade_id;

    public $level_id;

    public $status;

    public $archiveStatus = 'active';

    public $bulkSchoolId;

    public $bulkTerm;

    public $bulkStatusCounts = [];

    public $bulkFromStatus;

    public $bulkToStatus;

    public $bulkInScopeCount = 0;

    public $bulkProcessing = false;

    public $inputs = [
        'school_id' => null,
        'activities' => [],
    ];

    protected $queryString = [
        'school_id' => ['except' => ''],
        'grade_id' => ['except' => ''],
        'level_id' => ['except' => ''],
        'status' => ['except' => ''],
        'archiveStatus' => ['except' => 'active'],
    ];

    protected $listeners = [
        'archiveExamConfirm',
        'bulkArchiveExamsConfirm',
        'restoreExamConfirm',
        'bulkRestoreExamsConfirm',
    ];

    public function mount($school_id = null)
    {
        if ($school_id != null) {
            $this->school_id = $school_id;
        }

        $this->inputs['activities'] = [];
    }

    public function manageSearch()
    {
        $this->resetPage();
    }

    public function refreshActiviteis()
    {
        $this->validate([
            'inputs.school_id' => 'required',
            'inputs.activities' => 'required|array|min:1',
        ]);

        $exams = Exam::where('school_id', $this->inputs['school_id'])->get();

        foreach ($exams as $exam) {
            $activities = ExamActivityQuery::baseQuery($exam)
                ->whereIn('type', $this->inputs['activities'])
                ->get();

            foreach ($this->inputs['activities'] as $activity) {
                $exam->{$activity.'_activities'} =
                    $activities
                        ->where('type', $activity)
                        ->pluck('id')
                        ->toArray();
            }

            $exam->save();
        }

        Exam::where('school_id', $this->inputs['school_id'])
            ->where('status', 'pending')
            ->update(['status' => 'active']);

        $this->dispatch(
            'swal:toast',
            title: 'School exams activities refreshed successfully.',
            icon: 'success',
            modal: '#staticBackdrop'
        );
    }

    public function resyncStudents()
    {
        $reconciled = 0;

        Student::query()
            ->when($this->school_id, fn ($q) => $q->where('school_id', $this->school_id))
            ->whereNotNull('level_id')
            ->orderBy('id')
            ->chunkById(200, function ($students) use (&$reconciled) {
                foreach ($students as $student) {
                    Exam::reconcileStudentExams($student);
                    $reconciled++;
                }
            });

        $this->dispatch(
            'swal:toast',
            title: $reconciled > 0
                ? "{$reconciled} student(s) reconciled successfully."
                : 'No students matched the current filters to reconcile.',
            icon: 'success',
        );
    }

    public function resetFilters()
    {
        $this->school_id = '';
        $this->grade_id = '';
        $this->level_id = '';
        $this->status = '';
        $this->archiveStatus = 'active';
        $this->resetPage();
    }

    public function archiveExam($id)
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return;
        }

        if ($exam->status === 'active') {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Cannot archive active exam',
                text: 'Active exams cannot be archived. Please change the exam status first.',
            );

            return;
        }

        $this->dispatch(
            'confirmDelete',
            text: 'This exam will be archived and hidden from active lists and student access.',
            id: $id,
            emitBack: 'archiveExamConfirm',
        );
    }

    public function archiveExamConfirm($id)
    {
        $exam = Exam::find($id);

        if (! $exam) {
            return;
        }

        if ($exam->status === 'active') {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Cannot archive active exam',
                text: 'Active exams cannot be archived. Please change the exam status first.',
            );

            return;
        }

        $exam->delete();

        $this->dispatch(
            'swal:toast',
            title: 'Exam archived successfully.',
            icon: 'success',
        );
    }

    public function restoreExam($id)
    {
        $this->dispatch(
            'confirmRestore',
            text: 'This exam will be restored and become active in lists again.',
            id: $id,
            emitBack: 'restoreExamConfirm',
        );
    }

    public function restoreExamConfirm($id)
    {
        $exam = Exam::withTrashed()->find($id);

        if (! $exam || ! $exam->trashed()) {
            return;
        }

        $exam->restore();

        $this->dispatch(
            'swal:toast',
            title: 'Exam restored successfully.',
            icon: 'success',
        );
    }

    public function bulkArchiveExams()
    {
        if (! getPermissions('exams', 'delete')) {
            return;
        }

        $activeCount = (clone $this->bulkArchiveEligibleQuery())->where('status', 'active')->count();

        if ($activeCount > 0) {
            $this->dispatch(
                'swal:alert',
                icon: 'error',
                title: 'Cannot bulk archive',
                text: "{$activeCount} active exam(s) match your filters. Active exams cannot be archived. Change their status or adjust filters first.",
            );

            return;
        }

        $count = $this->bulkArchiveEligibleQuery()->count();

        if ($count === 0) {
            $this->dispatch(
                'swal:toast',
                title: 'No active exams match the current filters to archive.',
                icon: 'warning',
            );

            return;
        }

        $this->dispatch(
            'confirmBulkArchive',
            text: "All {$count} active exam(s) matching the applied filters will be archived. "
                .'They will be hidden from active lists and student access. You can restore them later.',
            emitBack: 'bulkArchiveExamsConfirm',
        );
    }

    public function bulkArchiveExamsConfirm()
    {
        if (! getPermissions('exams', 'delete')) {
            return;
        }

        $query = $this->bulkArchiveEligibleQuery();
        $archived = 0;

        DB::transaction(function () use ($query, &$archived) {
            $query->chunkById(200, function ($exams) use (&$archived) {
                foreach ($exams as $exam) {
                    if ($exam->trashed() || $exam->status === 'active') {
                        continue;
                    }

                    $exam->delete();
                    $archived++;
                }
            });
        });

        $this->resetPage();

        if ($archived === 0) {
            $this->dispatch(
                'swal:alert',
                icon: 'warning',
                title: 'No exams archived',
                text: 'No active exams matched the current filters.',
            );

            return;
        }

        $this->dispatch(
            'swal:alert',
            icon: 'success',
            title: 'Bulk archive complete',
            text: "{$archived} exam(s) were archived successfully.",
        );
    }

    public function bulkRestoreExams()
    {
        if (! getPermissions('exams', 'delete')) {
            return;
        }

        $count = $this->bulkRestoreEligibleQuery()->count();

        if ($count === 0) {
            $this->dispatch(
                'swal:toast',
                title: 'No archived exams match the current filters to restore.',
                icon: 'warning',
            );

            return;
        }

        $this->dispatch(
            'confirmBulkRestore',
            text: "All {$count} archived exam(s) matching the applied filters will be restored.",
            emitBack: 'bulkRestoreExamsConfirm',
        );
    }

    public function bulkRestoreExamsConfirm()
    {
        if (! getPermissions('exams', 'delete')) {
            return;
        }

        $query = $this->bulkRestoreEligibleQuery();
        $restored = 0;

        DB::transaction(function () use ($query, &$restored) {
            $query->chunkById(200, function ($exams) use (&$restored) {
                foreach ($exams as $exam) {
                    if (! $exam->trashed()) {
                        continue;
                    }

                    $exam->restore();
                    $restored++;
                }
            });
        });

        $this->resetPage();

        if ($restored === 0) {
            $this->dispatch(
                'swal:alert',
                icon: 'warning',
                title: 'No exams restored',
                text: 'No archived exams matched the current filters.',
            );

            return;
        }

        $this->dispatch(
            'swal:alert',
            icon: 'success',
            title: 'Bulk restore complete',
            text: "{$restored} exam(s) were restored successfully.",
        );
    }

    protected function loadExamsQuery()
    {
        return Exam::query()
            ->applyArchiveFilters($this->archiveStatus)
            ->when($this->school_id, fn ($query) => $query->where('school_id', $this->school_id))
            ->when($this->grade_id, fn ($query) => $query->where('grade_id', $this->grade_id))
            ->when($this->level_id, fn ($query) => $query->whereJsonContains('level_ids', (int) $this->level_id))
            ->when($this->status, fn ($query) => $query->where('status', $this->status));
    }

    protected function bulkArchiveEligibleQuery()
    {
        if ($this->archiveStatus === 'archived') {
            return Exam::query()->whereRaw('0 = 1');
        }

        $query = $this->loadExamsQuery();

        if ($this->archiveStatus === 'all') {
            $query->whereNull('exams.deleted_at');
        }

        return $query;
    }

    protected function bulkRestoreEligibleQuery()
    {
        if ($this->archiveStatus === 'active') {
            return Exam::query()->whereRaw('0 = 1');
        }

        $query = $this->loadExamsQuery();

        if ($this->archiveStatus === 'all') {
            $query->whereNotNull('exams.deleted_at');
        }

        return $query;
    }

    public function deleteExam($id)
    {
        $this->archiveExam($id);
    }

    public function examDelete($id)
    {
        $this->archiveExamConfirm($id);
    }

    public function changeStatus($event, $ex)
    {
        $exam = Exam::find($ex);

        if (! $exam) {
            return;
        }

        $exam->update(['status' => $event]);
        $this->dispatch(
            'swal:toast',
            title: 'Exam status updated successfully.',
            icon: 'success',
        );
    }

    public function openBulkStatusModal($school_id = null)
    {
        $this->bulkSchoolId = $school_id;
        $this->bulkTerm = null;
        $this->bulkStatusCounts = [];
        $this->bulkFromStatus = null;
        $this->bulkToStatus = null;
        $this->bulkInScopeCount = 0;

        $this->refreshBulkStatusCounts();
        $this->dispatch('open-bulk-status-modal');
    }

    public function updatedBulkSchoolId()
    {
        $this->refreshBulkStatusCounts();
    }

    public function updatedBulkTerm()
    {
        $this->refreshBulkStatusCounts();
    }

    public function updatedBulkFromStatus()
    {
        if (! empty($this->bulkToStatus) && $this->bulkToStatus === $this->bulkFromStatus) {
            $this->bulkToStatus = null;
        }
    }

    public function updatedBulkToStatus()
    {
        if (! empty($this->bulkFromStatus) && $this->bulkFromStatus === $this->bulkToStatus) {
            $this->bulkFromStatus = null;
        }
    }

    public function refreshBulkStatusCounts()
    {
        $query = Exam::query()
            ->when($this->bulkSchoolId, fn ($q) => $q->where('school_id', $this->bulkSchoolId))
            ->when($this->bulkTerm, fn ($q) => $q->where('term', $this->bulkTerm));

        $counts = $query->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->bulkStatusCounts = $counts;
        $this->bulkInScopeCount = array_sum($counts);
    }

    public function bulkUpdateStatus()
    {
        $this->validate([
            'bulkSchoolId' => 'required',
            'bulkFromStatus' => 'required',
            'bulkToStatus' => 'required|different:bulkFromStatus',
        ]);

        $this->bulkProcessing = true;

        $query = Exam::query()
            ->when($this->bulkSchoolId, fn ($q) => $q->where('school_id', $this->bulkSchoolId))
            ->when($this->bulkTerm, fn ($q) => $q->where('term', $this->bulkTerm))
            ->where('status', $this->bulkFromStatus);

        $inScope = $query->count();

        if ($inScope === 0) {
            $this->bulkProcessing = false;
            $this->dispatch(
                'swal:toast',
                title: 'No exams found matching the selected filters and status.',
                icon: 'warning',
            );

            return;
        }

        $changed = 0;

        DB::transaction(function () use ($query, &$changed) {
            $changed = $query->update(['status' => $this->bulkToStatus]);
        });

        $this->bulkProcessing = false;

        $this->dispatch(
            'swal:toast',
            title: "{$changed} exam(s) updated from {$this->bulkFromStatus} to {$this->bulkToStatus}.",
            icon: 'success',
        );

        $this->dispatch('close-bulk-status-modal');
        $this->refreshBulkStatusCounts();
    }

    public function render()
    {
        $exams = $this->loadExamsQuery()
            ->with('Grade', 'School')
            ->orderByDESC('created_at')
            ->paginate(21);

        $gradeIds = (clone $this->loadExamsQuery())->distinct()->pluck('grade_id');

        $schools = School::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->get();

        $grades = Grade::query()
            ->select('id', 'name', 'number')
            ->whereNotNull('number')
            ->when(
                $gradeIds->isNotEmpty(),
                fn ($q) => $q->whereIn('id', $gradeIds),
                fn ($q) => $q->whereRaw('0 = 1')
            )
            ->orderBy('number')
            ->get();

        $levels = Level::query()
            ->orderBy('number')
            ->get(['id', 'name', 'number']);

        $terms = (clone $this->loadExamsQuery())
            ->select('term')
            ->distinct()
            ->orderBy('term')
            ->pluck('term');

        return view('livewire.admin.exams.all-exams', [
            'exams' => $exams,
            'schools' => $schools,
            'grades' => $grades,
            'levels' => $levels,
            'terms' => $terms,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Exams',
            'pageTitle' => 'Exams',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Exams' => '#',
            ],
        ]);
    }
}
