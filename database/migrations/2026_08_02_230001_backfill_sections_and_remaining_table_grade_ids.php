<?php

use App\Support\BackfillTableGradeIds;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(BackfillTableGradeIds::class)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data backfill is not reversed.
    }
};
