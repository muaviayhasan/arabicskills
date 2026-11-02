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
 * Marking a writing paper.
 *
 * A writing answer arrives one of two ways. Photographs of handwritten work
 * are stored as a serialized list of upload ids; an answer typed on the
 * keyboard tab is stored as plain text. Both pages that show one to an admin
 * unserialized it either way, and unserialize() on plain text raises a
 * warning that Laravel turns into an exception, so a single student typing
 * their answer took the whole marking page down with a 500.
 *
 * It was not one student: 128 typed answers across 46 attempts.
 */
class CheckExamWritingAnswerTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    private StudentExam $attempt;

    private Question $question;

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

        $activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Writing Mark Round',
            'title' => 'Writing activity',
            'activity' => 'Body',
            'type' => 'writing',
        ]);

        $this->question = Question::create([
            'activity_id' => $activity->id,
            'question' => 'Describe your school',
            'type' => 'writing',
        ]);

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Writing Mark Round',
            'status' => 'active',
            'writing_activities' => [$activity->id],
            'writing_time' => '00:40',
        ]);

        $this->attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
            'writing_status' => ['status' => 'attempted'],
        ]);
    }

    private function record(?string $answer): void
    {
        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->attempt->student_id,
            'question_id' => $this->question->id,
            'answer' => $answer,
            'type' => 'writing',
        ]);
    }

    private function markingPage(): TestResponse
    {
        return $this->actingAs($this->admin, 'admin')
            ->get('/adminino/check-exam/writing/'.$this->attempt->id);
    }

    /** The case that was returning a 500. */
    public function test_a_typed_writing_answer_can_be_marked(): void
    {
        $this->record('صف العادات والتقاليد');

        $this->markingPage()
            ->assertOk()
            ->assertSee('صف العادات والتقاليد', false);
    }

    public function test_a_long_typed_answer_with_line_breaks_is_shown(): void
    {
        $this->record("first line\nsecond line");

        $this->markingPage()->assertOk()->assertSee('second line', false);
    }

    /**
     * Handed-in photographs still render as pictures rather than as the
     * serialized string.
     */
    public function test_uploaded_pages_still_render_as_images(): void
    {
        $this->record(serialize(['11', '12']));

        $response = $this->markingPage()->assertOk();

        $response->assertDontSee('a:2:{', false);
    }

    public function test_an_empty_writing_answer_says_so(): void
    {
        $this->record('');

        $this->markingPage()
            ->assertOk()
            ->assertSee('Student not attempted this question or answer not found', false);
    }

    /**
     * Text that merely looks like it might be serialized must not be fed to
     * unserialize() either.
     */
    public function test_text_that_resembles_serialized_data_is_treated_as_text(): void
    {
        $this->record('a:1:{not really serialized');

        $this->markingPage()->assertOk();
    }

    /** The read-only view of the same paper carries the identical branch. */
    public function test_the_paper_view_also_handles_a_typed_answer(): void
    {
        $this->record('typed into the keyboard tab');

        $this->actingAs($this->admin, 'admin')
            ->get('/adminino/results/writing/'.$this->attempt->id)
            ->assertOk()
            ->assertSee('typed into the keyboard tab', false);
    }
}
