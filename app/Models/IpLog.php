<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpLog extends Model
{
    use HasFactory;

    public function loggable()
    {
        return $this->morphTo();
    }

    protected $fillable = [
        'ip',
        'location',
        'path',
        'username',
    ];
}
