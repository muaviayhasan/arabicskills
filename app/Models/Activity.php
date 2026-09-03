<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'level_id',
        'grade_id',
        'term',
        'title',
        'activity',
        'image',
        'type',
        'lang',
    ];

    public function Question()
    {
        return $this->hasMany(Question::class, 'activity_id');
    }

    public function assignedLevel()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function Grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}
