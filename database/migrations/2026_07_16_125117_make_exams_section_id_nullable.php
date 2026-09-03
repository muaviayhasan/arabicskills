<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->change();
        });

        // Keep existing section_id values for legacy section-specific exams.
        // New grade-wide exams are created with section_id = null.
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable(false)->change();
        });
    }
};
