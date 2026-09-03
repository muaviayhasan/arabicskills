<?php

namespace App\Livewire\Admin\Admins;

use App\Models\Admin;
use Livewire\Component;
use Livewire\WithPagination;

class AllAdmins extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';
    public $searchWord;
    public $searchColumn;
    protected $listeners = [
        'AdminDelete', 'manageSearch'
    ];

    public function mount($admin_id = null)
    {
        if ($admin_id != null) {
            $this->searchColumn = 'id';
            $this->searchWord = $admin_id;
        }
    }

    public function manageSearch($searchWord, $searchColumn)
    {
        $this->resetPage();
        $this->searchWord = $searchWord;
        $this->searchColumn = $searchColumn;
    }

    public function deleteAdmin($id)
    {
        $this->dispatch(
            'confirmDelete',

            text: 'Admin record will be deleted permanently.',
            id: $id,
            emitBack: 'AdminDelete',
        );
    }

    public function AdminDelete($id)
    {
        $emp = Admin::find($id);
        @unlink(storage_path('app/public/admins/' . $emp->image));
        $emp->delete();
        $this->dispatch(
            'swal:toast',
            title: 'Admin deleted successfully.',
            icon: 'success',
        );
    }

    public function render()
    {
        $admins = Admin::when($this->searchColumn && $this->searchColumn != 'name', function ($query) {
            $query->where($this->searchColumn, 'LIKE', "%{$this->searchWord}%");
        })
            ->when($this->searchColumn && $this->searchColumn == 'name', function ($query) {
                $query->where('first_name', 'LIKE', "%{$this->searchWord}%")->orWhere('last_name', 'LIKE', "%{$this->searchWord}%");
            })
            ->with('AdminRole')
            ->paginate(6);

        return view('livewire.admin.admins.all-admins', ['admins' => $admins])->layout('layouts.base')->layoutData([
            'title' => 'Admins',
            'pageTitle' => 'Admins',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Admins' => "#",
            ],
        ]);
    }
}
