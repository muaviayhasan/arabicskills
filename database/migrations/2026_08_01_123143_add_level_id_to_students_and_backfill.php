<?php

use App\Support\BackfillConfig;
use App\Support\BackfillLevelIds;
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
        if (! Schema::hasColumn('students', 'level_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->after('level');
            });
        }

        if (! $this->foreignKeyExists('students', 'students_level_id_foreign')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('level_id')
                    ->references('id')
                    ->on('levels')
                    ->nullOnDelete();
            });
        }

        app(BackfillLevelIds::class)->run(BackfillConfig::forStudents());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if ($this->foreignKeyExists('students', 'students_level_id_foreign')) {
                $table->dropForeign(['level_id']);
            }

            if (Schema::hasColumn('students', 'level_id')) {
                $table->dropColumn('level_id');
            }
        });
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
