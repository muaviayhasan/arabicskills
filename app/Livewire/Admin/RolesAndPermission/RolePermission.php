<?php

namespace App\Livewire\Admin\RolesAndPermission;

use Exception;
use Livewire\Component;
use App\Livewire\Concerns\WithTableSorting;
use App\Models\AdminRole;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\AdminPermission;
use Illuminate\Support\Facades\DB;
use App\Models\AdminRolePermission;

class RolePermission extends Component
{
    use WithPagination;
    use WithTableSorting;
    protected $paginationTheme = 'bootstrap';
    public $new_role;
    protected $listeners = [
        'roleDelete'
    ];


    public function addRole()
    {
        $this->validate([
            'new_role' => 'required|string'
        ]);
        try {
            DB::beginTransaction();

            $new = new AdminRole;
            $new->name = ucwords($this->new_role);
            $new->slug = Str::slug($this->new_role);

            $new->save();

            foreach (AdminPermission::all() as $permission) {
                AdminRolePermission::insert([
                    'role_id'       => $new->id,
                    'permission_id' => $permission->id,
                    'view'          => '0',
                    'add'           => '0',
                    'edit'          => '0',
                    'delete'        => '0',
                ]);
            }

            Db::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: "New admin role added",
                modal: "#addRole"
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


    public function deleteRole($id)
    {
        $this->dispatch(
            'confirmDelete',
            text: 'Admin role will be deleted permanently.',
            id: $id,
            emitBack: 'roleDelete',
        );
    }


    public function roleDelete($id)
    {
        try {
            DB::beginTransaction();

            $empRole = AdminRole::find($id);

            $empRole->delete();

            Db::commit();

            $this->dispatch(
                'swal:alert',
                icon: 'success',
                title: 'Admin role deleted successfully',
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

    /**
     * @return array<string, string>
     */
    protected function sortableColumns(): array
    {
        return [
            'role' => 'admin_roles.name',
        ];
    }

    public function render()
    {
        $roles = AdminRole::with('AdminRolePermission.AdminPermission');
        $roles = $this->applySorting($roles, 'admin_roles.id', 'asc')->paginate(3);
        return view('livewire.admin.roles-and-permission.role-permission', ['roles' => $roles])->layout('layouts.base')->layoutData([
            'title' => 'Role and Permission',
            'pageTitle' => 'Admin Roles and Permission Management',
            'breadcrumb' => [
                'Dashboard' => route('admin.dashboard'),
                'Role Permission' => '#'
            ],
        ]);
    }
}
