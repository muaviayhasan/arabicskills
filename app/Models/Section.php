<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_id', 'name', 'school_id'
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    public function Grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function School()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function Student()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    public function Exam()
    {
        return $this->hasMany(Exam::class, 'section_id');
    }
}
