<?php

namespace App\Livewire\Admin\Schools;

use App\Livewire\Concerns\WithTableSorting;
use App\Models\Admin;
use App\Models\DeviceTest;
use App\Models\Exam;
use App\Models\IpLog;
use App\Models\Result;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AllSchools extends Component
{
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';
    public $searchWord;

    protected $queryString = [
        'searchWord' => ['except' => ''],
    ];

    protected $listeners = [
        'SchoolDelete'
    ];

    public function mount($school_id = null)
    {
        if ($school_id != null) {
            $this->searchWord = $school_id;
        }
    }

    public function manageSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->searchWord = '';
        $this->resetPage();
    }

    public function deleteSchool($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'School record will be deleted permanently.',
            id: $id,
            emitBack: 'SchoolDelete',
        );
    }

    public function SchoolDelete($id)
    {
        $school = School::find($id);

        if (! $school) {
            $this->dispatch(
                'swal:toast',
                title: 'School not found.',
                icon: 'error',
            );

            return;
        }

        DB::transaction(function () use ($school) {
            $sectionIds = Section::where('school_id', $school->id)->pluck('id');

            // exams.school_id is SET NULL, so grade-wide exams would otherwise
            // survive the delete as orphans with no school.
            $examIds = Exam::withTrashed()
                ->where('school_id', $school->id)
                ->when($sectionIds->isNotEmpty(), fn ($q) => $q->orWhereIn('section_id', $sectionIds))
                ->pluck('id');

            $studentIds = Student::withTrashed()
                ->where('school_id', $school->id)
                ->pluck('id');

            // Every student_exam reachable from either side of the delete.
            $studentExamIds = StudentExam::query()
                ->when($studentIds->isNotEmpty(), fn ($q) => $q->orWhereIn('student_id', $studentIds))
                ->when($examIds->isNotEmpty(), fn ($q) => $q->orWhereIn('exam_id', $examIds))
                ->pluck('id');

            if ($studentExamIds->isNotEmpty()) {
                // results and take_exams are SET NULL on student_exam_id, so
                // they must be removed explicitly or they linger unlinked.
                Result::whereIn('student_exam_id', $studentExamIds)->delete();

                $this->deleteAnswerUploads(TakeExam::whereIn('student_exam_id', $studentExamIds));
                TakeExam::whereIn('student_exam_id', $studentExamIds)->delete();

                StudentExam::whereIn('id', $studentExamIds)->delete();
            }

            if ($studentIds->isNotEmpty()) {
                $this->deleteAnswerUploads(TakeExam::whereIn('student_id', $studentIds));
                TakeExam::whereIn('student_id', $studentIds)->delete();

                DeviceTest::whereIn('student_id', $studentIds)->delete();

                // ip_logs is polymorphic — no foreign key, so nothing cascades.
                IpLog::where('loggable_type', Student::class)
                    ->whereIn('loggable_id', $studentIds)
                    ->delete();

                // student photos are Upload rows plus a file on disk.
                Student::withTrashed()
                    ->whereIn('id', $studentIds)
                    ->whereNotNull('image')
                    ->pluck('image')
                    ->each(fn ($image) => $this->safeDeleteImage($image));

                Student::withTrashed()->whereIn('id', $studentIds)->forceDelete();
            }

            if ($examIds->isNotEmpty()) {
                Exam::withTrashed()->whereIn('id', $examIds)->forceDelete();
            }

            Section::where('school_id', $school->id)->delete();

            // admins.school_id is SET NULL, and a null school_id means "can see
            // every school" (RestrictsToAdminSchool). Archive them instead so a
            // school admin is not silently promoted. Soft delete — restorable.
            Admin::where('school_id', $school->id)->delete();

            $this->safeDeleteImage($school->logo);
            $school->delete();
        });

        $this->resetPage();

        $this->dispatch(
            'swal:toast',
            title: 'School and all related records deleted successfully.',
            icon: 'success',
        );
    }

    /**
     * Speaking answers hold a single upload id; writing answers hold a
     * serialized array of them. The files must go before the rows do.
     */
    protected function deleteAnswerUploads($query): void
    {
        (clone $query)
            ->whereIn('type', ['writing', 'speaking'])
            ->chunkById(200, function ($answers) {
                foreach ($answers as $answer) {
                    if ($answer->type === 'speaking') {
                        $this->safeDeleteImage($answer->answer);

                        continue;
                    }

                    $ids = is_string($answer->answer) ? @unserialize($answer->answer) : [];

                    foreach (is_array($ids) ? $ids : [] as $id) {
                        $this->safeDeleteImage($id);
                    }
                }
            });
    }

    /**
     * A missing or already-removed file must not abort the whole delete.
     */
    protected function safeDeleteImage($image): void
    {
        if (empty($image)) {
            return;
        }

        try {
            delete_image($image);
        } catch (\Throwable) {
            // Nothing to clean up.
        }
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
            'students' => 'student_count',
        ];
    }

    public function render()
    {
        $schools = School::when($this->searchWord, function ($query) {
            $query->where(function($q) {
                $q->where('name', 'LIKE', "%{$this->searchWord}%")
                  ->orWhere('email', 'LIKE', "%{$this->searchWord}%")
                  ->orWhere('id', 'LIKE', "%{$this->searchWord}%");
            });
        })
            ->withCount('Student')
            ->tap(fn ($query) => $this->applySorting($query, 'created_at', 'desc'))
            ->paginate(6);

        return view('livewire.admin.schools.all-schools', ['schools' => $schools])->layout('layouts.base')->layoutData([
            'title' => 'Schools',
            'pageTitle' => 'Schools',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Schools' => "#",
            ],
        ]);
    }
    
}
