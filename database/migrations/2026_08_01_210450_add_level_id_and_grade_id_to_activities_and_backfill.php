<?php

use App\Support\BackfillActivityLevelGradeIds;
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
        if (! Schema::hasColumn('activities', 'level_id')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->unsignedBigInteger('level_id')->nullable()->after('level');
            });
        }

        if (! Schema::hasColumn('activities', 'grade_id')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->unsignedBigInteger('grade_id')->nullable()->after('level_id');
            });
        }

        if (! $this->foreignKeyExists('activities', 'activities_level_id_foreign')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->foreign('level_id')
                    ->references('id')
                    ->on('levels')
                    ->nullOnDelete();
            });
        }

        if (! $this->foreignKeyExists('activities', 'activities_grade_id_foreign')) {
            Schema::table('activities', function (Blueprint $table) {
                $table->foreign('grade_id')
                    ->references('id')
                    ->on('grades')
                    ->nullOnDelete();
            });
        }

        app(BackfillActivityLevelGradeIds::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if ($this->foreignKeyExists('activities', 'activities_grade_id_foreign')) {
                $table->dropForeign(['grade_id']);
            }

            if ($this->foreignKeyExists('activities', 'activities_level_id_foreign')) {
                $table->dropForeign(['level_id']);
            }

            if (Schema::hasColumn('activities', 'grade_id')) {
                $table->dropColumn('grade_id');
            }

            if (Schema::hasColumn('activities', 'level_id')) {
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
