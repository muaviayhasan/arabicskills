<?php

namespace App\Libraries;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class PermissionsLibrary
{
    private $admin_id = 0;
    private $admin_role_id = 0;
    private $admin_roles = 'admin_roles';
    private $admin_permissions = 'admin_permissions';
    private $admin_role_permission = 'admin_role_permissions';
    private $permissions = [];

    public function __construct()
    {
        if (Auth::guard('admin')->check()) {
            $this->admin_id = Auth::guard('admin')->user()->id;
            $this->admin_role_id = Auth::guard('admin')->user()->role_id;
        } else {
            exit('Admin not logged in.');
        }
    }

    public function initPermissions()
    {
        $query = DB::table($this->admin_roles . ' as ar')
            ->select(
                'ar.id as role_id',
                'ap.id as permission_id',
                'ap.key',
                'ap.view as default_view',
                'ap.add as default_add',
                'ap.edit as default_edit',
                'ap.delete as default_delete',
                'arp.view as role_view',
                'arp.add as role_add',
                'arp.edit as role_edit',
                'arp.delete as role_delete'
            )
            ->leftJoin($this->admin_role_permission . ' as arp', function ($join) {
                $join->on('arp.role_id', '=', 'ar.id')
                    ->where('ar.id', '=', DB::raw($this->admin_role_id));
            })
            ->leftJoin($this->admin_permissions . ' as ap', 'arp.permission_id', '=', 'ap.id')
            ->get();

        foreach ($query as $row) {
            $view = ($row->default_view) ? ((isset($row->role_view) && $row->role_view) ? $row->role_view : 0) : 0;
            $add = ($row->default_add) ? ((isset($row->role_add) && $row->role_add) ? $row->role_add : 0) : 0;
            $edit = ($row->default_edit) ? ((isset($row->role_edit) && $row->role_edit) ? $row->role_edit : 0) : 0;
            $delete = ($row->default_delete) ? ((isset($row->role_delete) && $row->role_delete) ? $row->role_delete : 0) : 0;

            if (isset($row->key)) {
                $this->permissions[$row->role_id][$row->key] = [
                    'view' => $view,
                    'add' => $add,
                    'edit' => $edit,
                    'delete' => $delete
                ];
            }
        }
    }

    public function getPermissions()
    {
        return $this->permissions[$this->admin_role_id];
    }

    public function permit($key, $permission, $role_id = NULL)
    {
        if (!is_null($role_id))
            $this->admin_role_id = $role_id;

        if (isset($this->permissions[$this->admin_role_id][$key][$permission]) && $this->permissions[$this->admin_role_id][$key][$permission] == 1) {
            return true;
        } else {
            throw new Exception("Permission denied.");
        }
    }

    public function hasPermission($key, $permission, $role_id = NULL)
    {
        if (!is_null($role_id))
            $this->admin_role_id = $role_id;

        return (isset($this->permissions[$this->admin_role_id][$key][$permission]) && $this->permissions[$this->admin_role_id][$key][$permission] == 1);
    }
}
