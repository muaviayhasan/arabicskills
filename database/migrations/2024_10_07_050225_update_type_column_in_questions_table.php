<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', [
                'MCQs',
                'true-false',
                'blanks',
                'typing',
                'rearrange',
                'match',
                'writing',
                'speaking',
                'match_words'
            ])->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->enum('type', [
                'MCQs',
                'true-false',
                'blanks',
                'typing',
                'rearrange',
                'match',
                'writing',
                'speaking'
            ])->change();
        });
    }
};
