<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            DELETE se1 FROM student_exams se1
            INNER JOIN student_exams se2
                ON se1.student_id = se2.student_id
                AND se1.exam_id = se2.exam_id
                AND se1.id > se2.id
        ');

        Schema::table('student_exams', function (Blueprint $table) {
            $table->unique(['student_id', 'exam_id'], 'student_exams_student_exam_unique');
        });
    }

    public function down(): void
    {
        Schema::table('student_exams', function (Blueprint $table) {
            $table->dropUnique('student_exams_student_exam_unique');
        });
    }
};
