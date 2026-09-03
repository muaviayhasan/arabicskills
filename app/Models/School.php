<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone_number',
        'email',
        'principal',
        'school_type',
        'establishment_year',
        'website',
        'logo',
        'additional_notes',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    protected function principal(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    public function Grade()
    {
        return $this->hasMany(Grade::class, 'school_id');
    }

    public function Student()
    {
        return $this->hasMany(Student::class, 'school_id');
    }

    public function Exam()
    {
        return $this->hasMany(Exam::class, 'school_id');
    }
}
