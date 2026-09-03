<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'question',
        'options',
        'correct_answer',
        'image',
        'type',
    ];

    protected function options(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => unserialize($value),
        );
    }

    public function Activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function TakeExam()
    {
        return $this->hasOne(TakeExam::class, 'question_id');
    }
}
