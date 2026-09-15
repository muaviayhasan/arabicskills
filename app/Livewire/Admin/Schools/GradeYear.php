<?php

namespace App\Livewire\Admin\Schools;

use App\Livewire\Concerns\WithTableSorting;
use App\Models\Grade;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GradeYear extends Component
{
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $searchWord = '';

    public $number;

    public $editingGradeId;

    protected $queryString = [
        'searchWord' => ['except' => ''],
    ];

    protected $listeners = [
        'gradeDelete',
    ];

    public function manageSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->searchWord = '';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->number = null;
        $this->editingGradeId = null;
    }

    public function openEditModal(int $gradeId): void
    {
        $this->resetValidation();
        $grade = Grade::findOrFail($gradeId);
        $this->editingGradeId = $grade->id;
        $this->number = $grade->number;
    }

    public function saveGrade(): void
    {
        if ($this->editingGradeId) {
            $this->updateGrade();

            return;
        }

        $this->createGrade();
    }

    protected function createGrade(): void
    {
        if (! getPermissions('classes', 'add')) {
            return;
        }

        $this->validate([
            'number' => 'required|integer|min:1|unique:grades,number',
        ]);

        Grade::create([
            'number' => (int) $this->number,
            'name' => Grade::nameFromNumber((int) $this->number),
            'admin_id' => Auth::guard('admin')->id(),
        ]);

        $this->number = null;

        $this->dispatch(
            'swal:alert',
            title: 'Grade added successfully.',
            icon: 'success',
            modal: '#gradeModal',
        );
    }

    protected function updateGrade(): void
    {
        if (! getPermissions('classes', 'edit')) {
            return;
        }

        $this->validate([
            'number' => 'required|integer|min:1|unique:grades,number,'.$this->editingGradeId,
        ]);

        $grade = Grade::findOrFail($this->editingGradeId);
        $grade->update([
            'number' => (int) $this->number,
            'name' => Grade::nameFromNumber((int) $this->number),
            'admin_id' => Auth::guard('admin')->id(),
        ]);

        $this->editingGradeId = null;
        $this->number = null;

        $this->dispatch(
            'swal:alert',
            title: 'Grade updated successfully.',
            icon: 'success',
            modal: '#gradeModal',
        );
    }

    public function deleteGrade(int $id): void
    {
        $this->dispatch(
            'confirmDelete',
            text: 'This grade will be deleted permanently.',
            id: $id,
            emitBack: 'gradeDelete',
        );
    }

    public function gradeDelete(int $id): void
    {
        if (! getPermissions('classes', 'delete')) {
            return;
        }

        Grade::findOrFail($id)->delete();

        $this->dispatch(
            'swal:toast',
            title: 'Grade deleted successfully.',
            icon: 'success',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'id' => 'grades.id',
            // Names are generated as "Year N", so ordering on `number` keeps
            // Year 2 before Year 10 rather than sorting them as text.
            'name' => 'grades.number',
            'admin' => 'admins.first_name',
        ];
    }

    protected function applySortJoins($query)
    {
        $query->select('grades.*');

        return $this->sortField === 'admin'
            ? $query->leftJoin('admins', 'admins.id', '=', 'grades.admin_id')
            : $query;
    }

    public function render()
    {
        $classes = Grade::query()
            ->with('Admin')
            ->when($this->searchWord, function ($query) {
                $search = trim($this->searchWord);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('number', 'LIKE', "%{$search}%");
                });
            });

        $classes = $this->applySorting($classes, 'grades.number', 'asc')->paginate(20);

        return view('livewire.admin.schools.grade-year', [
            'classes' => $classes,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Grades',
            'pageTitle' => 'Grades',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Grades' => '#',
            ],
        ]);
    }
}
