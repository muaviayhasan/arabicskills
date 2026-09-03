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
        Schema::table('activities', function (Blueprint $table) {
            $table->string('term')->nullable()->after('level');
        });

        // Set all existing exams status to expired
        DB::table('exams')->update(['status' => 'expired']);

        // Assign all existing activities to the first term from options
        $firstTerm = null;

        $option = DB::table('options')->where('key', 'terms')->first();

        if ($option && $option->value) {
            $terms = @unserialize($option->value);
            if (is_array($terms) && count($terms) > 0) {
                $firstTerm = $terms[0];
            }
        }

        if ($firstTerm) {
            DB::table('activities')->update(['term' => $firstTerm]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('term');
        });
    }
};
