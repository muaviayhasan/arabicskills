<?php

namespace App\Livewire\Admin\RolesAndPermission;

use Exception;
use Livewire\Component;
use App\Models\AdminRole;
use Illuminate\Support\Str;
use App\Models\AdminPermission;
use Illuminate\Support\Facades\DB;
use App\Models\AdminRolePermission;

class EditRolePermission extends Component
{
    public $role;
    public $name;
    public $inputs = [];
    public $allPermissions;

    protected $listeners = [
        'roleDelete'
    ];

    public function mount($role_id)
    {
        try {

            $this->role = AdminRole::find($role_id);
            $this->name = $this->role->name;

            $this->allPermissions = AdminPermission::with(['roles' => function ($query) use ($role_id) {
                $query->where(
                    'role_id',
                    $role_id
                );
            }])->get();

            foreach ($this->allPermissions as $index => $perm) {
                $hasPermission = $perm->roles->first();
                $this->inputs[$index] = [
                    'permission_id' => $perm->id,
                    'role_id' => $role_id,
                    'view' => $perm->view ? ($hasPermission ? ($hasPermission['pivot']['view'] == 1 ? true : false) : 0) : 0,
                    'add' => $perm->add ? ($hasPermission ? ($hasPermission['pivot']['add'] == 1 ? true : false) : 0) : 0,
                    'edit' => $perm->edit ? ($hasPermission ? ($hasPermission['pivot']['edit'] == 1 ? true : false) : 0) : 0,
                    'delete' => $perm->delete ? ($hasPermission ? ($hasPermission['pivot']['delete'] == 1 ? true : false) : 0) : 0,
                ];
            }

            // dd($this->inputs);
        } catch (Exception $e) {
            return redirect()->back();
        }
    }
    public function updateRolePermission()
    {

        try {
            DB::beginTransaction();

            foreach ($this->inputs as $data) {

                AdminRolePermission::updateOrCreate(
                    ['role_id' => $data['role_id'], 'permission_id' => $data['permission_id']],
                    [
                        'view' => $data['view'],
                        'add' => $data['add'],
                        'edit' => $data['edit'],
                        'delete' => $data['delete'],
                    ]
                );
            }

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Role permissions are updated successfully',
                url: route('admin.role-permission'),
            );
        } catch (Exception $e) {

            DB::rollback();

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    public function updateRole()
    {

        $this->validate([
            'name' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $this->role->update([
                'name' => $this->name,
                'slug' => Str::slug($this->name),
            ]);

            DB::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Role is updated successfully',
                url: route('admin.role-permission'),
            );
        } catch (Exception $e) {

            DB::rollback();

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    public function deleteRole()
    {
        if (getPermissions('admin_roles', 'delete'))
            $this->dispatch(
                'confirmDelete',
                text: 'Admin role will be deleted permanently.',
                id: $this->role->id,
                emitBack: 'roleDelete',
            );
        else
            return redirect()->route('access-denied');
    }


    public function roleDelete($id)
    {
        try {
            DB::beginTransaction();

            $this->role->delete();

            Db::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Admin role deleted successfully',
                url: route('admin.role-permission')
            );
        } catch (Exception $e) {

            DB::rollback();

            $this->dispatch(
                'swal:alert',
                icon: 'error',
                text: $e->getMessage(),
            );
        }
    }

    public function render()
    {
        return view('livewire.admin.roles-and-permission.edit-role-permission')->layout('layouts.base')->layoutData([
            'title' => 'Edit Role Permission',
            'pageTitle' => 'Edit Role Permission',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Role Permission' => route('admin.role-permission'),
                'Edit Role Permission' => '#'
            ],
        ]);
    }
}
