<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'device_info',
        'test_results',
        'overall_status',
    ];

    protected $casts = [
        'device_info'   => 'array',
        'test_results'  => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
