<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    use HasFactory;
    public function AdminRolePermission()
    {
        return $this->hasMany(AdminRolePermission::class, 'permission_id');
    }
    public function Roles()
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_permissions', 'permission_id', 'role_id')
        ->withPivot('view', 'add', 'edit', 'delete');
    }
}
