<?php

use App\Support\CleanupLegacyLevelColumns;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $cleanup = app(CleanupLegacyLevelColumns::class);
        $method = new \ReflectionMethod($cleanup, 'pruneStaleOldGradeReferences');
        $method->setAccessible(true);
        $method->invoke($cleanup);
    }

    public function down(): void
    {
        // Not reversed.
    }
};
