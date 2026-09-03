<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentExamResolutionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @return array{school: School, grade: Grade, level: Level, otherLevel: Level, section: Section|null}
     */
    private function resolutionFixtures(): array
    {
        $school = School::query()->orderBy('id')->first();
        $grade = Grade::query()->whereNotNull('number')->orderBy('number')->first();
        $levels = Level::query()->orderBy('number')->limit(2)->get();

        if ($school === null || $grade === null || $levels->count() < 2) {
            $this->markTestSkipped('Need at least one school, numbered grade, and two levels.');
        }

        $section = Section::query()
            ->where('school_id', $school->id)
            ->where('grade_id', $grade->id)
            ->orderBy('id')
            ->first();

        return [
            'school' => $school,
            'grade' => $grade,
            'level' => $levels->first(),
            'otherLevel' => $levels->last(),
            'section' => $section,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeStudent(array $fixtures, array $overrides = []): Student
    {
        return new Student(array_merge([
            'school_id' => $fixtures['school']->id,
            'grade_id' => $fixtures['grade']->id,
            'section_id' => $fixtures['section']?->id,
            'level_id' => $fixtures['level']->id,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function persistStudent(array $fixtures, array $overrides = []): Student
    {
        return Student::create(array_merge([
            'school_id' => $fixtures['school']->id,
            'grade_id' => $fixtures['grade']->id,
            'section_id' => $fixtures['section']?->id,
            'level_id' => $fixtures['level']->id,
            'name' => 'Test Student',
            'registration' => 'REG-'.Str::uuid(),
            'year' => (int) date('Y'),
            'user_name' => 'user_'.Str::random(8),
            'password' => 'secret',
            'nationality' => 'Test',
            'category' => 'Test',
        ], $overrides));
    }

    private function createExam(array $fixtures, array $overrides = []): Exam
    {
        if (! Schema::hasColumn('exams', 'level_ids')) {
            $this->markTestSkipped('exams.level_ids not migrated yet.');
        }

        return Exam::create(array_merge([
            'school_id' => $fixtures['school']->id,
            'grade_id' => $fixtures['grade']->id,
            'section_id' => null,
            'level_ids' => [$fixtures['level']->id],
            'term' => 'test-'.Str::uuid(),
            'status' => 'active',
            'reading_time' => '0:30',
            'listening_time' => '0:30',
            'writing_time' => '0:30',
            'speaking_time' => '0:30',
            'sentences_structures_time' => '0:30',
        ], $overrides));
    }

    public function test_student_has_resolvable_placement_requires_school_grade_and_level(): void
    {
        $fixtures = $this->resolutionFixtures();

        $complete = $this->makeStudent($fixtures);
        $this->assertTrue(Exam::studentHasResolvablePlacement($complete));

        $missingLevel = $this->makeStudent($fixtures, ['level_id' => null]);
        $this->assertFalse(Exam::studentHasResolvablePlacement($missingLevel));
    }

    public function test_resolves_exam_when_school_grade_and_level_match(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->makeStudent($fixtures);

        $exam = $this->createExam($fixtures, [
            'term' => 'match-term-'.Str::uuid(),
        ]);

        $resolved = Exam::resolveForStudent($student, ['active'], $exam->term);

        $this->assertNotNull($resolved);
        $this->assertSame($exam->id, $resolved->id);
    }

    public function test_does_not_resolve_exam_when_level_not_in_level_ids(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->makeStudent($fixtures);

        $exam = $this->createExam($fixtures, [
            'level_ids' => [$fixtures['otherLevel']->id],
            'term' => 'other-level-term-'.Str::uuid(),
        ]);

        $resolved = Exam::resolveForStudent($student, ['active'], $exam->term);

        $this->assertNull($resolved);
    }

    public function test_does_not_resolve_exams_when_student_level_id_is_null(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->makeStudent($fixtures, ['level_id' => null]);

        $this->createExam($fixtures, [
            'term' => 'null-level-term-'.Str::uuid(),
        ]);

        $resolved = Exam::resolvedExamsForStudent($student, ['active']);

        $this->assertTrue($resolved->isEmpty());
    }

    public function test_does_not_resolve_exam_for_wrong_grade(): void
    {
        $fixtures = $this->resolutionFixtures();
        $otherGrade = Grade::query()
            ->whereNotNull('number')
            ->where('id', '!=', $fixtures['grade']->id)
            ->orderBy('number')
            ->first();

        if ($otherGrade === null) {
            $this->markTestSkipped('Need a second numbered grade.');
        }

        $student = $this->makeStudent($fixtures);

        $exam = $this->createExam($fixtures, [
            'grade_id' => $otherGrade->id,
            'term' => 'wrong-grade-term-'.Str::uuid(),
        ]);

        $resolved = Exam::resolveForStudent($student, ['active'], $exam->term);

        $this->assertNull($resolved);
    }

    public function test_pick_resolved_prefers_matching_section_exam_over_grade_wide(): void
    {
        $fixtures = $this->resolutionFixtures();

        if ($fixtures['section'] === null) {
            $this->markTestSkipped('Need a section for section-scoped resolution test.');
        }

        $student = $this->makeStudent($fixtures, ['section_id' => $fixtures['section']->id]);
        $term = 'section-pick-'.Str::uuid();

        $gradeWide = $this->createExam($fixtures, [
            'section_id' => null,
            'term' => $term,
            'created_at' => now()->subDay(),
        ]);

        $sectionExam = $this->createExam($fixtures, [
            'section_id' => $fixtures['section']->id,
            'term' => $term,
            'created_at' => now(),
        ]);

        $candidates = collect([$gradeWide, $sectionExam]);

        $picked = Exam::pickResolvedFromCandidates($candidates, $student);

        $this->assertNotNull($picked);
        $this->assertSame($sectionExam->id, $picked->id);
    }

    public function test_pick_resolved_accepts_grade_wide_exam_for_any_section(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->makeStudent($fixtures);
        $term = 'grade-wide-pick-'.Str::uuid();

        $gradeWide = $this->createExam($fixtures, [
            'section_id' => null,
            'term' => $term,
        ]);

        $picked = Exam::pickResolvedFromCandidates(collect([$gradeWide]), $student);

        $this->assertNotNull($picked);
        $this->assertSame($gradeWide->id, $picked->id);
    }

    public function test_apply_student_level_scope_excludes_all_when_level_id_missing(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->makeStudent($fixtures, ['level_id' => null]);

        $this->createExam($fixtures, [
            'term' => 'scope-null-term-'.Str::uuid(),
        ]);

        $count = Exam::query()
            ->where('school_id', $student->school_id)
            ->where('grade_id', $student->grade_id)
            ->where('status', 'active')
            ->tap(fn ($q) => Exam::applyStudentLevelScope($q, $student))
            ->count();

        $this->assertSame(0, $count);
    }

    public function test_reconcile_student_exams_creates_row_for_resolved_exam(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->persistStudent($fixtures);

        $exam = $this->createExam($fixtures, [
            'term' => 'reconcile-term-'.Str::uuid(),
        ]);

        Exam::reconcileStudentExams($student);

        $this->assertDatabaseHas('student_exams', [
            'student_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function test_reconcile_is_idempotent_for_same_student_exam_pair(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->persistStudent($fixtures);

        $this->createExam($fixtures, [
            'term' => 'idempotent-term-'.Str::uuid(),
        ]);

        Exam::reconcileStudentExams($student);
        Exam::reconcileStudentExams($student);

        $count = \App\Models\StudentExam::query()
            ->where('student_id', $student->id)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_reconcile_student_exams_removes_stale_unchecked_rows_for_archived_exam(): void
    {
        $fixtures = $this->resolutionFixtures();
        $student = $this->persistStudent($fixtures);

        $exam = $this->createExam($fixtures, [
            'term' => 'archive-reconcile-'.Str::uuid(),
        ]);

        \App\Models\StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
        ]);

        $exam->delete();

        Exam::reconcileStudentExams($student);

        $this->assertDatabaseMissing('student_exams', [
            'student_id' => $student->id,
            'exam_id' => $exam->id,
        ]);
    }

    public function test_reconcile_keeps_one_exam_per_term_when_grade_wide_and_section_exist(): void
    {
        $fixtures = $this->resolutionFixtures();

        if ($fixtures['section'] === null) {
            $this->markTestSkipped('Need a section for per-term dedupe test.');
        }

        $student = $this->persistStudent($fixtures, [
            'section_id' => $fixtures['section']->id,
        ]);

        $term = 'one-per-term-'.Str::uuid();

        $gradeWide = $this->createExam($fixtures, [
            'section_id' => null,
            'term' => $term,
            'created_at' => now()->subDay(),
        ]);

        $sectionExam = $this->createExam($fixtures, [
            'section_id' => $fixtures['section']->id,
            'term' => $term,
            'created_at' => now(),
        ]);

        Exam::reconcileStudentExams($student);

        $linkedExamIds = \App\Models\StudentExam::query()
            ->where('student_id', $student->id)
            ->pluck('exam_id')
            ->all();

        $this->assertSame([$sectionExam->id], $linkedExamIds);
        $this->assertNotContains($gradeWide->id, $linkedExamIds);
    }
}
