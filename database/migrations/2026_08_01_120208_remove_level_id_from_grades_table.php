<?php

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
        if (! Schema::hasColumn('grades', 'level_id')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn('level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('grades', 'level_id')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            $table->unsignedBigInteger('level_id')->nullable()->after('level');
        });
    }
};
