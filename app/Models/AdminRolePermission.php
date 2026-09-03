<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminRolePermission extends Model
{
    use HasFactory;
    protected $fillable = [
        'role_id', 'permission_id', 'view', 'add', 'edit', 'delete'
    ];
    
    public function AdminRole()
    {
        return $this->belongsTo(AdminRole::class, 'role_id', 'id');
    }

    public function AdminPermission()
    {
        return $this->belongsTo(AdminPermission::class, 'permission_id');
    }
}
