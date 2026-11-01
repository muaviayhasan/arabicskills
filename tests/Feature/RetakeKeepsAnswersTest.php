<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A reopened paper opens with the work the student had already done.
 *
 * Reopening gives a student a fresh clock. Without this they would also get a
 * blank form, and would have to redo everything they had already answered,
 * which is the opposite of what reopening is for.
 */
class RetakeKeepsAnswersTest extends TestCase
{
    use DatabaseTransactions;

    private Student $student;

    private StudentExam $attempt;

    /** @var array<string, Question> */
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
            'term' => 'Retake Round',
            'title' => 'Retake activity',
            'activity' => 'Body',
            'type' => 'reading',
        ]);

        foreach (['MCQs', 'true-false', 'typing', 'blanks', 'rearrange'] as $type) {
            $this->questions[$type] = Question::create([
                'activity_id' => $activity->id,
                'question' => $type === 'rearrange' ? 'one,two,three' : "A {$type} question",
                'type' => $type,
                // The model unserializes options on read but has no setter,
                // so the stored value has to be serialized here.
                'options' => serialize($type === 'MCQs'
                    ? ['a' => 'Alpha', 'b' => 'Bravo', 'c' => 'Charlie', 'd' => 'Delta']
                    : []),
            ]);
        }

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Retake Round',
            'status' => 'active',
            'reading_activities' => [$activity->id],
            'reading_time' => '00:40',
        ]);

        $this->attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
        ]);
    }

    private function record(string $type, ?string $answer): void
    {
        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'question_id' => $this->questions[$type]->id,
            'answer' => $answer,
            'type' => $type === 'blanks' ? 'blanks' : ($type === 'rearrange' ? 'rearrange' : $type),
        ]);
    }

    private function openPaper(): TestResponse
    {
        return $this->actingAs($this->student, 'web')->get('/student/exams/reading-exam');
    }

    public function test_a_chosen_option_comes_back_ticked(): void
    {
        $this->record('MCQs', 'c');

        $html = $this->openPaper()->assertOk()->getContent();

        $id = $this->questions['MCQs']->id;

        $this->assertMatchesRegularExpression(
            '/name="answer\['.$id.'\]\[answer\]" value="c"\s*checked/',
            $html,
            'the option the student chose should be selected again'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/name="answer\['.$id.'\]\[answer\]" value="a"\s*checked/',
            $html,
            'an option they did not choose must not be selected'
        );
    }

    public function test_a_true_false_choice_comes_back_ticked(): void
    {
        $this->record('true-false', 'T');

        $html = $this->openPaper()->assertOk()->getContent();
        $id = $this->questions['true-false']->id;

        $this->assertMatchesRegularExpression(
            '/name="answer\['.$id.'\]\[answer\]" value="T"\s*checked/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/name="answer\['.$id.'\]\[answer\]" value="F"\s*checked/',
            $html
        );
    }

    public function test_typed_text_comes_back_in_the_box(): void
    {
        $this->record('typing', 'مشمش');

        $html = $this->openPaper()->assertOk()->getContent();

        $this->assertStringContainsString('مشمش', $html, 'the typed answer should be in the textarea');
    }

    public function test_a_filled_blank_comes_back(): void
    {
        $this->record('blanks', 'the missing word');

        $this->assertStringContainsString(
            'the missing word',
            $this->openPaper()->assertOk()->getContent()
        );
    }

    /**
     * The rearranged order is kept in a hidden field and the words on screen
     * are put back by script.js, which reads the same value from data-saved.
     */
    public function test_a_rearranged_order_is_handed_back_to_the_page(): void
    {
        $this->record('rearrange', '["three","one","two"]');

        $html = $this->openPaper()->assertOk()->getContent();
        $id = $this->questions['rearrange']->id;

        $this->assertStringContainsString('data-saved="'.e('["three","one","two"]').'"', $html);
        $this->assertMatchesRegularExpression(
            '/id="question_'.$id.'_answer"[^>]*value="'.preg_quote(e('["three","one","two"]'), '/').'"/',
            $html,
            'the hidden field should already hold the order they chose'
        );
    }

    /**
     * A blank row is not an answer: a paper that auto-submitted writes one for
     * every textarea on it, and those must not come back as content.
     */
    public function test_a_blank_row_is_not_treated_as_an_answer(): void
    {
        $this->record('typing', '');
        $this->record('MCQs', '{}');

        $html = $this->openPaper()->assertOk()->getContent();
        $id = $this->questions['MCQs']->id;

        $this->assertDoesNotMatchRegularExpression(
            '/name="answer\['.$id.'\]\[answer\]" value="[a-d]"\s*checked/',
            $html
        );
        $this->assertStringNotContainsString('>{}</textarea>', $html);
    }

    /**
     * Nothing is pre-filled for a student who has not answered yet, so a first
     * sitting is unaffected.
     */
    public function test_a_first_sitting_is_not_prefilled(): void
    {
        $html = $this->openPaper()->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\[answer\]" value="[a-d]"\s*checked/', $html);
    }

    /**
     * A recorder cannot be loaded with the previous take, so an empty speaking
     * submission means "not re-recorded", not "throw the recording away".
     */
    public function test_resubmitting_speaking_without_a_new_recording_keeps_the_old_one(): void
    {
        $question = $this->questions['MCQs'];

        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'question_id' => $question->id,
            'answer' => '4321',
            'type' => 'speaking',
        ]);

        $this->actingAs($this->student, 'web')
            ->postJson('/student/exams/speaking-exam', [
                'exam_id' => $this->attempt->id,
                'answer' => [$question->id => ['answer' => '', 'type' => 'speaking']],
            ])
            ->assertOk();

        $this->assertSame(
            '4321',
            TakeExam::where('student_exam_id', $this->attempt->id)
                ->where('question_id', $question->id)->value('answer'),
            'the earlier recording must survive a submit that carries no new audio'
        );
    }

    /**
     * Same for uploaded writing pages, which cannot be put back into a file
     * input either.
     */
    public function test_resubmitting_writing_without_a_new_upload_keeps_the_pages(): void
    {
        $question = $this->questions['typing'];
        $pages = serialize(['11', '12']);

        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'question_id' => $question->id,
            'answer' => $pages,
            'type' => 'writing',
        ]);

        $this->actingAs($this->student, 'web')
            ->postJson('/student/exams/writing-exam', [
                'exam_id' => $this->attempt->id,
                'answer' => [$question->id => ['answer' => '', 'type' => 'writing']],
            ])
            ->assertOk();

        $this->assertSame(
            $pages,
            TakeExam::where('student_exam_id', $this->attempt->id)
                ->where('question_id', $question->id)->value('answer')
        );
    }

    /**
     * But a typed answer is pre-filled, so clearing it is a real deletion and
     * has to be honoured.
     */
    public function test_clearing_a_typed_writing_answer_is_honoured(): void
    {
        $question = $this->questions['typing'];

        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'question_id' => $question->id,
            'answer' => 'first draft',
            'type' => 'writing',
        ]);

        $this->actingAs($this->student, 'web')
            ->postJson('/student/exams/writing-exam', [
                'exam_id' => $this->attempt->id,
                'answer' => [$question->id => ['answer' => 'second draft', 'type' => 'writing']],
            ])
            ->assertOk();

        $this->assertSame(
            'second draft',
            TakeExam::where('student_exam_id', $this->attempt->id)
                ->where('question_id', $question->id)->value('answer')
        );
    }
}
