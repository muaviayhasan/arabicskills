<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Submitting a paper.
 *
 * This is the one request a student cannot afford to lose, so each case here
 * is something that used to go wrong: answers written against somebody else's
 * attempt, a stale id answered with a 500 instead of json, a missing type
 * taking the whole paper down with it, and a query per answer at the moment a
 * room full of students runs out of time together.
 */
class ExamSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    private Student $student;

    private Exam $exam;

    private StudentExam $attempt;

    /** @var list<Question> */
    private array $questions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        $this->student = $student;

        $activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Submission Round',
            'title' => 'Submission activity',
            'activity' => 'Body',
            'type' => 'reading',
        ]);

        foreach (range(1, 4) as $i) {
            $this->questions[] = Question::create([
                'activity_id' => $activity->id,
                'question' => "Question {$i}",
                'type' => 'true-false',
            ]);
        }

        $this->exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Submission Round',
            'status' => 'active',
            'reading_activities' => [$activity->id],
            'reading_time' => '00:40',
        ]);

        $this->attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $this->exam->id,
            'checked' => false,
        ]);
    }

    /** @return array<int, array{answer: string, type: string}> */
    private function answers(string $answer = 'true', ?string $type = 'true-false'): array
    {
        $payload = [];

        foreach ($this->questions as $question) {
            $payload[$question->id] = array_filter([
                'answer' => $answer,
                'type' => $type,
            ], fn ($v) => $v !== null);
        }

        return $payload;
    }

    private function submit(array $payload): TestResponse
    {
        return $this->actingAs($this->student, 'web')
            ->postJson('/student/exams/reading-exam', $payload);
    }

    public function test_a_student_submits_their_paper(): void
    {
        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers()])
            ->assertOk()
            ->assertJson(['message' => 'Exam Submitted Successfully']);

        $stored = TakeExam::where('student_exam_id', $this->attempt->id)->get();

        $this->assertCount(4, $stored);
        $this->assertEqualsCanonicalizing(
            array_map(fn ($q) => $q->id, $this->questions),
            $stored->pluck('question_id')->all()
        );
        $this->assertSame(['true'], $stored->pluck('answer')->unique()->values()->all());
        $this->assertSame([$this->student->id], $stored->pluck('student_id')->unique()->values()->all());

        $this->assertSame('attempted', $this->attempt->fresh()->reading_status['status']);
    }

    /**
     * exam_id is a hidden form field, so it is whatever the browser sends. It
     * used to go straight into find(), which let a student write answers onto
     * another student's attempt and mark their paper submitted.
     */
    public function test_a_student_cannot_submit_against_another_students_attempt(): void
    {
        $other = Student::query()->where('id', '!=', $this->student->id)->first();

        if ($other === null) {
            $this->markTestSkipped('Need a second student.');
        }

        $victim = StudentExam::create([
            'student_id' => $other->id,
            'exam_id' => $this->exam->id,
            'checked' => false,
        ]);

        $this->submit(['exam_id' => $victim->id, 'answer' => $this->answers()])
            ->assertNotFound();

        $this->assertSame(0, TakeExam::where('student_exam_id', $victim->id)->count());
        $this->assertNull($victim->fresh()->reading_status['status'] ?? null);
    }

    /**
     * find() returned null and reading a status off it raised an Error, which
     * `catch (Exception)` did not catch: no rollback, a 500 HTML page instead
     * of json, and the student told only that something went wrong.
     */
    public function test_an_unknown_attempt_is_a_404_not_a_500(): void
    {
        $this->submit(['exam_id' => 99999999, 'answer' => $this->answers()])
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_a_missing_attempt_id_is_a_404_not_a_500(): void
    {
        $this->submit(['answer' => $this->answers()])->assertNotFound();
        $this->submit(['exam_id' => 'nonsense', 'answer' => $this->answers()])->assertNotFound();
    }

    public function test_a_checked_attempt_is_closed_to_submission(): void
    {
        $this->attempt->update(['checked' => true]);

        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers()])
            ->assertNotFound();

        $this->assertSame(0, TakeExam::where('student_exam_id', $this->attempt->id)->count());
    }

    /**
     * take_exams.type is an enum and the connection is in strict mode, so a
     * value outside it errors rather than coercing. The old per-skill defaults
     * ('reading' and friends) were not enum members, so an answer arriving
     * without a type would have rolled back the entire paper.
     */
    public function test_an_answer_with_no_type_falls_back_to_the_questions_own_type(): void
    {
        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers('true', null)])
            ->assertOk();

        $stored = TakeExam::where('student_exam_id', $this->attempt->id)->get();

        $this->assertCount(4, $stored);
        $this->assertSame(['true-false'], $stored->pluck('type')->unique()->values()->all());
    }

    public function test_an_unknown_type_is_not_written_to_the_enum(): void
    {
        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers('true', 'not-a-real-type')])
            ->assertOk();

        $this->assertSame(
            ['true-false'],
            TakeExam::where('student_exam_id', $this->attempt->id)->pluck('type')->unique()->values()->all()
        );
    }

    /**
     * Resubmitting has to land on the same row. It used to be keyed on an
     * updateOrCreate whose match included student_exam_id, and a retry after a
     * failed request is how 953 duplicate answer rows accumulated.
     */
    public function test_resubmitting_updates_the_answer_instead_of_duplicating_it(): void
    {
        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers('true')])->assertOk();
        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers('false')])->assertOk();

        $stored = TakeExam::where('student_exam_id', $this->attempt->id)->get();

        $this->assertCount(4, $stored, 'a resubmit must not add a second row per question');
        $this->assertSame(['false'], $stored->pluck('answer')->unique()->values()->all());
    }

    /**
     * The submit used to cost roughly two queries per answer, all of it
     * arriving at once when a whole room's time ran out together.
     */
    public function test_the_whole_paper_is_written_without_a_query_per_answer(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers()])->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            8,
            $count,
            "submitting 4 answers took {$count} queries; it should not scale with the answer count"
        );
    }

    public function test_answers_are_linked_to_the_attempt(): void
    {
        $this->submit(['exam_id' => $this->attempt->id, 'answer' => $this->answers()])->assertOk();

        $this->assertSame(
            4,
            StudentExam::withCount('TakeExam')->find($this->attempt->id)->take_exam_count,
            'the reopen guard and the absent filter both count answers through this relation'
        );
    }
}
