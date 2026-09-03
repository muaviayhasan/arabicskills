<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'school_id',
        'grade_id',
        'section_id',
        'name',
        'registration',
        'year',
        'user_name',
        'level_id',
        'nationality',
        'password',
        'category',
        'image',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => ucwords($value),
        );
    }

    public function Section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function Grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function assignedLevel()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function School()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function studentExams()
    {
        return $this->hasMany(StudentExam::class, 'student_id', 'id');
    }

    public function logs()
    {
        return $this->morphMany(IpLog::class, 'loggable');
    }

    public function scopeApplyArchiveFilters(Builder $query, ?string $archiveStatus = null, mixed $year = null): Builder
    {
        $archiveStatus = $archiveStatus ?: 'active';
        if ($archiveStatus === 'archived') {
            $query->onlyTrashed();
        } elseif ($archiveStatus === 'all') {
            $query->withTrashed();
        }
        if ($year) {
            $query->where('year', (int) $year);
        }

        return $query;
    }

    /**
     * Whether another active (non-archived) student already uses this registration + year.
     */
    public function hasActiveRegistrationConflict(): bool
    {
        if ($this->registration === null || $this->registration === '') {
            return false;
        }

        return static::query()
            ->where('registration', $this->registration)
            ->where('year', $this->year)
            ->whereKeyNot($this->id)
            ->exists();
    }
}
