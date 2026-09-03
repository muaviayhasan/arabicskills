<?php

use App\Support\CleanupLegacyLevelColumns;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(CleanupLegacyLevelColumns::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Destructive data cleanup is not reversed.
    }
};
