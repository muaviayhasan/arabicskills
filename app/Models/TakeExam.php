<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TakeExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'student_id',
        'student_exam_id',
        'answer',
        'type',
    ];

    public function StudentExam()
    {
        return $this->belongsTo(StudentExam::class, 'student_exam_id');
    }

    public function Question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
