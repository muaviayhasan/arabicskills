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
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            // $table->text('activity_ids');
            // $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            // $table->float('marks')->nullable();
            // $table->text('options')->nullable();
            $table->enum('type', ['image', 'text', 'audio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
