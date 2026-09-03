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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->text('question')->nullable();
            $table->text('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->string('image')->nullable();
            $table->enum('type', ['MCQs', 'true-false', 'blanks', 'typing', 'rearrange', 'match', 'writing', 'speaking']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
