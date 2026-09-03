<?php

use App\Support\SyncLegacyLevelsFromOptions;
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
        if (! Schema::hasColumn('levels', 'legacy_labels')) {
            Schema::table('levels', function (Blueprint $table) {
                $table->json('legacy_labels')->nullable()->after('number');
            });
        }

        app(SyncLegacyLevelsFromOptions::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            if (Schema::hasColumn('levels', 'legacy_labels')) {
                $table->dropColumn('legacy_labels');
            }
        });
    }
};
