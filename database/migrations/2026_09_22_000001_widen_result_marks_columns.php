<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Widen the results columns from VARCHAR(255) to TEXT.
 *
 * Each *_marks column holds a PHP-serialized array of one mark per question,
 * roughly 17 characters per question. Past about a dozen questions that no
 * longer fits in 255, and saving fails with:
 *
 *   SQLSTATE[22001]: Data too long for column 'listening_marks'
 *
 * which is what a 16-question listening paper hit. On a server without strict
 * mode the same write would silently truncate instead, leaving a corrupt
 * serialized string that cannot be read back.
 *
 * Raw SQL rather than ->change(), which needs doctrine/dbal; it is not
 * installed, so a change() migration would fail on deploy.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'reading_marks',
        'listening_marks',
        'writing_marks',
        'speaking_marks',
        'sentences_structures_marks',
        // Free text written by whoever marks the paper; 255 is just as tight.
        'remarks',
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            DB::statement("ALTER TABLE `results` MODIFY `{$column}` TEXT NULL");
        }
    }

    /**
     * Note: reversing this truncates anything already longer than 255
     * characters, which is the very data the change exists to allow.
     */
    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            DB::statement("ALTER TABLE `results` MODIFY `{$column}` VARCHAR(255) NULL");
        }
    }
};
