<?php

namespace App\Livewire\Admin\Levels;

use App\Livewire\Concerns\WithTableSorting;
use App\Models\Level;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class LevelsCrud extends Component
{
    use WithPagination;
    use WithTableSorting;

    protected $paginationTheme = 'bootstrap';

    public $searchWord = '';

    public $number;

    public $editingLevelId;

    protected $queryString = [
        'searchWord' => ['except' => ''],
    ];

    protected $listeners = [
        'levelDelete',
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
        $this->editingLevelId = null;
    }

    public function openEditModal(int $levelId): void
    {
        $this->resetValidation();
        $level = Level::findOrFail($levelId);
        $this->editingLevelId = $level->id;
        $this->number = $level->number;
    }

    public function saveLevel(): void
    {
        if ($this->editingLevelId) {
            $this->updateLevel();

            return;
        }

        $this->createLevel();
    }

    protected function createLevel(): void
    {
        if (! getPermissions('levels', 'add')) {
            return;
        }

        $this->validate([
            'number' => 'required|integer|min:1|unique:levels,number',
        ]);

        Level::create([
            'number' => (int) $this->number,
            'name' => Level::nameFromNumber((int) $this->number),
            'admin_id' => Auth::guard('admin')->id(),
        ]);

        $this->number = null;

        $this->dispatch(
            'swal:alert',
            title: 'Level added successfully.',
            icon: 'success',
            modal: '#levelModal',
        );
    }

    protected function updateLevel(): void
    {
        if (! getPermissions('levels', 'edit')) {
            return;
        }

        $this->validate([
            'number' => 'required|integer|min:1|unique:levels,number,'.$this->editingLevelId,
        ]);

        $level = Level::findOrFail($this->editingLevelId);
        $level->update([
            'number' => (int) $this->number,
            'name' => Level::nameFromNumber((int) $this->number),
            'admin_id' => Auth::guard('admin')->id(),
        ]);

        $this->editingLevelId = null;
        $this->number = null;

        $this->dispatch(
            'swal:alert',
            title: 'Level updated successfully.',
            icon: 'success',
            modal: '#levelModal',
        );
    }

    public function deleteLevel(int $id): void
    {
        $this->dispatch(
            'confirmDelete',
            text: 'This level will be deleted permanently.',
            id: $id,
            emitBack: 'levelDelete',
        );
    }

    public function levelDelete(int $id): void
    {
        if (! getPermissions('levels', 'delete')) {
            return;
        }

        Level::findOrFail($id)->delete();

        $this->dispatch(
            'swal:toast',
            title: 'Level deleted successfully.',
            icon: 'success',
        );
    }

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'id' => 'levels.id',
            // Names are always "Level N", so ordering on `number` keeps
            // Level 2 before Level 10 rather than sorting them as text.
            'name' => 'levels.number',
            'admin' => 'admins.first_name',
        ];
    }

    protected function applySortJoins($query)
    {
        $query->select('levels.*');

        return $this->sortField === 'admin'
            ? $query->leftJoin('admins', 'admins.id', '=', 'levels.admin_id')
            : $query;
    }

    public function render()
    {
        $levels = Level::query()
            ->with('Admin')
            ->when($this->searchWord, function ($query) {
                $search = trim($this->searchWord);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('number', 'LIKE', "%{$search}%");
                });
            });

        $levels = $this->applySorting($levels, 'levels.number', 'asc')->paginate(20);

        return view('livewire.admin.levels.levels-crud', [
            'levels' => $levels,
        ])->layout('layouts.base')->layoutData([
            'title' => 'Levels',
            'pageTitle' => 'Levels',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Levels' => '#',
            ],
        ]);
    }
}
