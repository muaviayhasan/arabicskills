<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentExam extends Model
{
    use HasFactory;

    public const SKILL_STATUS_COLUMNS = [
        'reading_status',
        'listening_status',
        'writing_status',
        'speaking_status',
        'sentences_structures_status',
    ];

    protected $fillable = [
        'exam_id',
        'student_id',
        'checked',
        'reading_status',
        'writing_status',
        'listening_status',
        'speaking_status',
        'sentences_structures_status',

    ];

    protected function listeningStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? unserialize($value) : [],
            set: fn ($value) => $value ? serialize($value) : serialize([]),
        );
    }

    protected function readingStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? unserialize($value) : [],
            set: fn ($value) => $value ? serialize($value) : serialize([]),
        );
    }

    protected function sentencesStructuresStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? unserialize($value) : [],
            set: fn ($value) => $value ? serialize($value) : serialize([]),
        );
    }

    protected function speakingStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? unserialize($value) : [],
            set: fn ($value) => $value ? serialize($value) : serialize([]),
        );
    }

    protected function writingStatus(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? unserialize($value) : [],
            set: fn ($value) => $value ? serialize($value) : serialize([]),
        );
    }

    public function Exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function Student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function TakeExam()
    {
        return $this->hasMany(TakeExam::class, 'student_exam_id');
    }

    public function Result()
    {
        return $this->hasOne(Result::class, 'student_exam_id');
    }

    public static function serializedStatusLikePattern(string $status): string
    {
        return '%s:6:"status";s:'.strlen($status).':"'.$status.'";%';
    }

    public function scopeWhereAnySkillStatus(Builder $query, string $status): Builder
    {
        if ($status === 'absent') {
            return $query->whereDoesntHave('TakeExam');
        }

        $pattern = self::serializedStatusLikePattern($status);

        return $query->where(function (Builder $q) use ($pattern) {
            foreach (self::SKILL_STATUS_COLUMNS as $column) {
                $q->orWhere($column, 'like', $pattern);
            }
        });
    }
}
