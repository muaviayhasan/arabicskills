<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'admin_id', 'number', 'old_grades',
    ];

    protected $casts = [
        'old_grades' => 'array',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    public static function nameFromNumber(int $number): string
    {
        return 'Year '.$number;
    }

    public function Section()
    {
        return $this->hasMany(Section::class, 'grade_id');
    }

    public function Admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function Exam()
    {
        return $this->hasMany(Exam::class, 'grade_id');
    }
}
