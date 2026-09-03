<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'admin_id',
        'legacy_labels',
    ];

    protected $casts = [
        'legacy_labels' => 'array',
    ];

    public function Admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public static function nameFromNumber(int $number): string
    {
        return 'Level '.$number;
    }
}
