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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('level', 10);
            $table->text('reading_activities')->nullable();
            $table->text('listening_activities')->nullable();
            $table->text('speaking_activities')->nullable();
            $table->text('writing_activities')->nullable();
            $table->string('reading_time')->nullable();
            $table->string('writing_time')->nullable();
            $table->string('listening_time')->nullable();
            $table->string('speaking_time')->nullable();
            $table->string('term');
            $table->enum('status', ['pending', 'active', 'suspended', 'expired']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
