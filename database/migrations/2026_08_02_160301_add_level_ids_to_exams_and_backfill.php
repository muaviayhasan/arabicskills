<?php

use App\Support\BackfillExamLevelGradeIds;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('exams', 'level_ids')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->json('level_ids')->nullable()->after('level');
            });
        }

        app(BackfillExamLevelGradeIds::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'level_ids')) {
                $table->dropColumn('level_ids');
            }
        });
    }
};
