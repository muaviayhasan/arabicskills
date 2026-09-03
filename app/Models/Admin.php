<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class Admin extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'role_id',
        'school_id',
        'first_name',
        'last_name',
        'email',
        'email_verified_at',
        'password',
        'image',
    ];
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Hash::make($value),
        );
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }


    public function AdminRole()
    {
        return $this->belongsTo(AdminRole::class, 'role_id');
    }

    public function School()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function AdminRolePermission()
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id', 'role_id');
    }

    public function logs()
    {
        return $this->morphMany(IpLog::class, 'loggable');
    }
}
