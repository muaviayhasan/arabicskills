<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All four are nullable: null means "not recorded yet", which is distinct
     * from an explicit No. Existing students start as null and are filled in
     * by re-uploading the student sheet.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('nationality');
            $table->boolean('sen')->nullable()->after('gender');
            $table->boolean('gifted_talented')->nullable()->after('sen');
            $table->boolean('citizen')->nullable()->after('gifted_talented');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['gender', 'sen', 'gifted_talented', 'citizen']);
        });
    }
};
