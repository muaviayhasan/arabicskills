<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Default mark ranges, from the client's "rang mark sheet".
 *
 * Each scale stores two thresholds, where In line and Above begin; Below is
 * everything under the first. Stored that way, ranges cannot overlap or leave
 * a gap, and a decimal mark such as 9.5 still falls in exactly one band.
 */
return new class extends Migration
{
    private const DEFAULTS = [
        'mark_skill_in_line_from' => '10',
        'mark_skill_above_from' => '15',
        'mark_total_in_line_from' => '50',
        'mark_total_above_from' => '75',
        'mark_display' => 'number',
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $key => $value) {
            // Never overwrite a value an admin has already set.
            if (DB::table('options')->where('key', $key)->doesntExist()) {
                DB::table('options')->insert(['key' => $key, 'value' => $value]);
            }
        }
    }

    public function down(): void
    {
        DB::table('options')->whereIn('key', array_keys(self::DEFAULTS))->delete();
    }
};
