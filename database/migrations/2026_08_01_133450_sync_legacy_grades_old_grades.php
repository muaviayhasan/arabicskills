<?php

use App\Support\SyncLegacyGradesToOldGrades;
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
        if (! Schema::hasColumn('grades', 'old_grades')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->json('old_grades')->nullable()->after('number');
            });
        }

        app(SyncLegacyGradesToOldGrades::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'old_grades')) {
                $table->dropColumn('old_grades');
            }
        });
    }
};
