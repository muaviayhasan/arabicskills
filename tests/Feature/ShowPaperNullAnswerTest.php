<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Viewing a marked paper when the student skipped a question.
 *
 * Nothing is written for a question a student never touched, so it has no
 * take_exams row at all. Most of this view reads the answer through
 * optional(), but the rearrange branch reaches straight through the relation,
 * and a missing row there took the page down with "Attempt to read property
 * answer on null".
 *
 * It needs a rearrange question with no answer and any other question with
 * one, which is the ordinary shape of a paper somebody left a gap in.
 */
class ShowPaperNullAnswerTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    private StudentExam $attempt;

    private Activity $activity;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::query()->whereNull('school_id')->first() ?? Admin::query()->first();
        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($admin === null || $student === null) {
            $this->markTestSkipped('Need an admin and a placed student.');
        }

        $this->admin = $admin;

        $this->activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Null Answer Round',
            'title' => 'Listening activity',
            'activity' => 'Body',
            'type' => 'listening',
        ]);

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Null Answer Round',
            'status' => 'active',
            'listening_activities' => [$this->activity->id],
            'listening_time' => '00:40',
        ]);

        $this->attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
            'listening_status' => ['status' => 'attempted'],
        ]);
    }

    private function question(string $type): Question
    {
        // Shaped like the real bank: every MCQs question there carries a..d,
        // and a match question carries choices and answers.
        $options = match ($type) {
            'MCQs' => ['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie', 'd' => 'Delta'],
            'match', 'match_words' => ['choice' => ['one', 'two'], 'answer' => ['first', 'second'], 'activity' => ['one', 'two']],
            default => [],
        };

        return Question::create([
            'activity_id' => $this->activity->id,
            'question' => $type === 'rearrange' ? 'one,two,three' : 'A question',
            'type' => $type,
            'options' => serialize($options),
            'correct_answer' => 'x',
        ]);
    }

    private function viewPaper(): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->get('/adminino/results/listening/'.$this->attempt->id);
    }

    /** The case that was returning a 500. */
    public function test_a_skipped_rearrange_question_does_not_break_the_paper(): void
    {
        $this->question('rearrange');   // never answered: no take_exams row

        $this->viewPaper()->assertOk();
    }

    public function test_a_skipped_question_of_every_type_is_survivable(): void
    {
        foreach (['MCQs', 'true-false', 'blanks', 'typing', 'rearrange', 'match'] as $type) {
            $this->question($type);
        }

        $this->viewPaper()->assertOk();
    }

    /** An answered rearrange question still renders its order. */
    public function test_an_answered_rearrange_question_still_shows(): void
    {
        $question = $this->question('rearrange');

        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->attempt->student_id,
            'question_id' => $question->id,
            'answer' => '["three","one","two"]',
            'type' => 'rearrange',
        ]);

        $this->viewPaper()->assertOk();
    }

    /** The marking page takes the same data. */
    public function test_the_marking_page_survives_it_too(): void
    {
        $this->question('rearrange');

        $this->actingAs($this->admin, 'admin')
            ->get('/adminino/check-exam/listening/'.$this->attempt->id)
            ->assertOk();
    }
}
