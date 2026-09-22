<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks imported from the school's own assessment sheet.
 *
 * Deliberately separate from `results`, which holds per-question answers from
 * exams sat inside this system. These are one score per skill, entered
 * elsewhere and uploaded, so they do not fit that shape.
 *
 * Judgments, the total's judgment and the expectation are not stored: they are
 * worked out from the marks and the admin's ranges whenever they are shown, so
 * changing a range on the Settings page updates every result at once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedSmallInteger('academic_year');
            $table->unsignedTinyInteger('round'); // 1, 2 or 3

            // Decimal, not integer: half marks are possible, and the ranges are
            // stored as lower bounds so a mark like 9.5 still lands in one band.
            foreach (['reading', 'listening', 'writing', 'speaking', 'sentences_structures'] as $skill) {
                $table->decimal($skill, 5, 2)->nullable();
            }

            // Kept in step with the skills on save, so the list can sort by it.
            $table->decimal('total', 6, 2)->nullable();

            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            // One set of marks per student, per round, per year.
            $table->unique(['student_id', 'academic_year', 'round'], 'assessment_marks_student_round_unique');
            $table->index(['academic_year', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_marks');
    }
};
