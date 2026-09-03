<?php

use App\Support\BackfillStudentGradeIds;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->foreignKeyExists('students', 'students_grade_id_foreign')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('grade_id')
                    ->references('id')
                    ->on('grades')
                    ->nullOnDelete();
            });
        }

        app(BackfillStudentGradeIds::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill is not reversed.
    }

    protected function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $constraintName]
        );

        return $result !== null;
    }
};
