<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_exam_id',
        'reading_marks',
        'sentences_structures_marks',
        'listening_marks',
        'writing_marks',
        'speaking_marks',
        'remarks',
    ];

    public function StudentExam()
    {
        return $this->belongsTo(StudentExam::class, 'student_exam_id');
    }

    protected function readingMarks(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value ? serialize($value) : serialize([]),
            get: fn($value) => $value ? unserialize($value) : [],
        );
    }

    protected function sentencesStructuresMarks(): Attribute
    {
        return Attribute::make(
            set: fn($value) => $value ? serialize($value) : serialize([]),
            get: fn($value) => $value ? unserialize($value) : [],
        );
    }

    protected function listeningMarks(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    protected function writingMarks(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    protected function speakingMarks(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }
}
