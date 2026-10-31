<?php

namespace Tests\Feature;

use App\Livewire\Student\Exams\SkillExams;
use App\Models\Activity;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The exam list warns a student when a skill they have already submitted is
 * missing an answer. That used to be worked out card by card, loading every
 * question and every answer; it is two aggregate queries now, and it has to
 * reach the same verdict.
 */
class SkillExamMissingAnswersTest extends TestCase
{
    use DatabaseTransactions;

    private Student $student;

    private Exam $exam;

    private Activity $activity;

    /** @var list<Question> */
    private array $questions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $student = Student::query()
            ->whereNotNull('level_id')
            ->whereNotNull('grade_id')
            ->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        $this->student = $student;

        $this->activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Missing Answers Round',
            'title' => 'Test activity',
            'activity' => 'Body',
            'type' => 'reading',
        ]);

        foreach (range(1, 3) as $i) {
            $this->questions[] = Question::create([
                'activity_id' => $this->activity->id,
                'question' => "Question {$i}",
                'type' => 'MCQs',
            ]);
        }

        $this->exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Missing Answers Round',
            'status' => 'active',
            'reading_activities' => [$this->activity->id],
            'reading_time' => '00:40',
        ]);
    }

    private function answer(Question $question, ?string $answer): void
    {
        TakeExam::create([
            'student_exam_id' => StudentExam::where('student_id', $this->student->id)
                ->where('exam_id', $this->exam->id)->value('id'),
            'student_id' => $this->student->id,
            'question_id' => $question->id,
            'answer' => $answer,
            'type' => 'MCQs',
        ]);
    }

    private function markReadingAttempted(): void
    {
        StudentExam::updateOrCreate(
            ['student_id' => $this->student->id, 'exam_id' => $this->exam->id],
            ['checked' => false, 'reading_status' => ['status' => 'attempted']]
        );
    }

    private function readingWarns(): bool
    {
        $component = Livewire::actingAs($this->student, 'web')->test(SkillExams::class)->instance();

        return $component->skillsWithMissingAnswers(['reading' => [$this->activity->id]])['reading'];
    }

    public function test_a_fully_answered_skill_does_not_warn(): void
    {
        $this->markReadingAttempted();

        foreach ($this->questions as $question) {
            $this->answer($question, 'B');
        }

        $this->assertFalse($this->readingWarns());
    }

    public function test_a_question_with_no_answer_row_warns(): void
    {
        $this->markReadingAttempted();

        $this->answer($this->questions[0], 'B');
        $this->answer($this->questions[1], 'C');
        // the third was never submitted

        $this->assertTrue($this->readingWarns());
    }

    /**
     * The exam pages write an empty string or '{}' when a student moves past
     * a question without touching it, so those count as unanswered.
     */
    public function test_an_empty_answer_warns(): void
    {
        $this->markReadingAttempted();

        $this->answer($this->questions[0], 'B');
        $this->answer($this->questions[1], '');
        $this->answer($this->questions[2], '{}');

        $this->assertTrue($this->readingWarns());
    }

    public function test_a_null_answer_warns(): void
    {
        $this->markReadingAttempted();

        $this->answer($this->questions[0], 'B');
        $this->answer($this->questions[1], 'C');
        $this->answer($this->questions[2], null);

        $this->assertTrue($this->readingWarns());
    }

    /**
     * A skill the student has not submitted yet is not warned about: it is
     * not finished, so nothing is missing from it.
     */
    public function test_a_skill_that_is_not_attempted_is_not_warned_about(): void
    {
        StudentExam::updateOrCreate(
            ['student_id' => $this->student->id, 'exam_id' => $this->exam->id],
            ['checked' => false, 'reading_status' => ['status' => 'unattempted']]
        );

        $this->assertFalse($this->readingWarns());
    }

    /**
     * The whole point of the rewrite: the check no longer costs a query per
     * skill, so a paper covering all five skills costs the same as one.
     */
    public function test_the_check_costs_the_same_whatever_the_paper_covers(): void
    {
        $this->markReadingAttempted();
        foreach ($this->questions as $question) {
            $this->answer($question, 'B');
        }

        $component = Livewire::actingAs($this->student, 'web')->test(SkillExams::class)->instance();

        $count = function (array $skills) use ($component): int {
            $queries = 0;
            DB::listen(function () use (&$queries) {
                $queries++;
            });
            $component->skillsWithMissingAnswers($skills);

            return $queries;
        };

        $one = $count(['reading' => [$this->activity->id]]);
        $five = $count([
            'reading' => [$this->activity->id],
            'listening' => [$this->activity->id],
            'writing' => [$this->activity->id],
            'speaking' => [$this->activity->id],
            'sentences_structures' => [$this->activity->id],
        ]);

        $this->assertSame($one, $five, "one skill cost {$one} queries, five cost {$five}");
        $this->assertLessThanOrEqual(2, $one);
    }
}
