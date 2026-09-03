<?php

namespace App\Livewire\Admin\Schools;

use App\Models\School;
use Livewire\Component;
use Livewire\WithPagination;

class AllSchools extends Component
{
    use WithPagination;
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
        $sch = School::find($id);
        delete_image($sch->logo);
        $sch->delete();
        $this->dispatch(
            'swal:toast',
            title: 'School deleted successfully.',
            icon: 'success',
        );
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
            ->orderByDesc('created_at')
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
