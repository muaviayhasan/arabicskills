<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The pages a student walks through to sit a paper. None of them may answer
 * with a 500: a student locked out by an error mid-round cannot be helped in
 * time, and the exam window does not wait.
 */
class StudentExamPagesSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private const SKILLS = ['reading', 'listening', 'writing', 'speaking', 'sentences_structures'];

    private function student(): Student
    {
        $student = Student::query()
            ->whereNotNull('level_id')
            ->whereNotNull('grade_id')
            ->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        return $student;
    }

    public function test_the_exam_list_opens(): void
    {
        $this->actingAs($this->student(), 'web')
            ->get(route('student.exams'))
            ->assertOk();
    }

    public function test_every_skill_instruction_page_opens(): void
    {
        $student = $this->student();

        foreach (self::SKILLS as $skill) {
            $response = $this->actingAs($student, 'web')
                ->get(route('student.exams-instructions', ['type' => $skill]));

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "the {$skill} instructions answered {$response->getStatusCode()}"
            );
        }
    }

    /**
     * The type comes straight off the URL. An unknown one used to read a
     * property off a missing row, and then ask for a route that does not
     * exist, so a typo in the address bar was a 500.
     */
    public function test_an_unknown_skill_is_not_a_server_error(): void
    {
        foreach (['nonsense', 'reading-exam', '../admin', 'READING'] as $type) {
            $response = $this->actingAs($this->student(), 'web')
                ->get('/student/exams/instruction/'.$type);

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "type '{$type}' answered {$response->getStatusCode()}"
            );
        }
    }

    public function test_every_skill_attempt_page_opens(): void
    {
        $student = $this->student();

        foreach (self::SKILLS as $skill) {
            $route = 'student.'.str_replace('_', '-', $skill).'-exam';

            $response = $this->actingAs($student, 'web')->get(route($route));

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "the {$skill} paper answered {$response->getStatusCode()}"
            );
        }
    }

    /**
     * A student with no level cannot be matched to a paper. Every page still
     * has to answer rather than fall over.
     */
    public function test_a_student_without_a_level_still_gets_a_page(): void
    {
        $student = $this->student();
        $student->level_id = null;

        $this->actingAs($student, 'web')->get(route('student.exams'))->assertOk();

        foreach (self::SKILLS as $skill) {
            $route = 'student.'.str_replace('_', '-', $skill).'-exam';
            $response = $this->actingAs($student, 'web')->get(route($route));

            $this->assertLessThan(500, $response->getStatusCode(), "{$skill} fell over for an unplaced student");
        }
    }

    public function test_the_exam_list_does_not_grow_queries_with_the_paper(): void
    {
        $student = $this->student();

        $queries = 0;
        \DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($student, 'web')->get(route('student.exams'))->assertOk();

        // A guard rail, not a target: the page reads one student, their exam
        // and the activities behind it, and should not scale past that.
        $this->assertLessThan(
            40,
            $queries,
            "the exam list ran {$queries} queries"
        );
    }

    public function test_the_list_survives_an_exam_with_no_activities(): void
    {
        $student = $this->student();

        Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Smoke Round',
            'status' => 'active',
        ]);

        $this->actingAs($student, 'web')->get(route('student.exams'))->assertOk();
    }
}
