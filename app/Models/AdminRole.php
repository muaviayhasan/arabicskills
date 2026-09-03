<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug'
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    public function Admin()
    {
        return $this->hasMany(Admin::class, 'role_id', 'id');
    }
    public function AdminRolePermission()
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id', 'id');
    }

    public function Permissions()
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permissions', 'role_id', 'permission_id')
            ->withPivot('view', 'add', 'edit', 'delete');
    }
}
