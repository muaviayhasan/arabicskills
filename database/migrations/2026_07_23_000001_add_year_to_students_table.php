<?php

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
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('registration');
        });

        DB::table('students')->update(['year' => 2026]);

        Schema::table('students', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable(false)->change();
            $table->dropUnique(['registration']);
            $table->unique(['registration', 'year']);
            $table->unique('user_name');
        });

        DB::table('options')->updateOrInsert(
            ['key' => 'current_academic_year'],
            ['value' => '2026']
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique(['registration', 'year']);
            $table->dropUnique(['user_name']);
            $table->unique('registration');
            $table->dropColumn('year');
        });

        DB::table('options')->where('key', 'current_academic_year')->delete();
    }
};
