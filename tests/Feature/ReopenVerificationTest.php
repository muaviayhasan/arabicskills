<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamCheck\AttemptedExams;
use App\Models\Activity;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use App\Support\ExamSkillStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verification of the reopen rule against the situations it exists for.
 *
 * The rule takes one exam-wide "has answers" flag, so this asks what happens
 * when one skill was answered and another auto-submitted blank, which is the
 * ordinary shape of a student who hit trouble part way through a round.
 */
class ReopenVerificationTest extends TestCase
{
    use DatabaseTransactions;

    private Student $student;

    private StudentExam $attempt;

    /** @var array<string, Activity> */
    private array $activities = [];

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

        foreach (['reading', 'listening'] as $skill) {
            $activity = Activity::create([
                'level_id' => $student->level_id,
                'grade_id' => $student->grade_id,
                'term' => 'Reopen Verification',
                'title' => ucfirst($skill).' activity',
                'activity' => 'Body',
                'type' => $skill,
            ]);

            Question::create([
                'activity_id' => $activity->id,
                'question' => 'Q',
                'type' => 'MCQs',
            ]);

            $this->activities[$skill] = $activity;
        }

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Reopen Verification',
            'status' => 'active',
            'reading_activities' => [$this->activities['reading']->id],
            'listening_activities' => [$this->activities['listening']->id],
            'reading_time' => '00:40',
            'listening_time' => '00:30',
        ]);

        $this->attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
        ]);
    }

    private function answerSkill(string $skill): void
    {
        TakeExam::create([
            'student_exam_id' => $this->attempt->id,
            'student_id' => $this->student->id,
            'question_id' => Question::where('activity_id', $this->activities[$skill]->id)->value('id'),
            'answer' => 'B',
            'type' => 'MCQs',
        ]);
    }

    /** @return array<string, string> */
    private function offered(): array
    {
        $exam = StudentExam::findOrFail($this->attempt->id);

        return ExamSkillStatus::reopenableSkills(
            $exam,
            ExamSkillStatus::answeredSkillsFor([$exam->id])[$exam->id] ?? []
        );
    }

    /**
     * The straightforward case the feature was built for: a paper that
     * auto-submitted with nothing recorded anywhere.
     */
    public function test_a_blank_auto_submitted_exam_is_offered(): void
    {
        $this->attempt->update([
            'reading_status' => ['status' => 'attempted'],
            'listening_status' => ['status' => 'attempted'],
        ]);

        $offered = $this->offered();

        $this->assertArrayHasKey('reading_status', $offered);
        $this->assertArrayHasKey('listening_status', $offered);
    }

    /**
     * A student who answered reading, then hit trouble on listening and had it
     * auto-submit blank. Listening is exactly what an admin needs to reopen,
     * and reading must stay protected.
     */
    public function test_one_answered_skill_and_one_blank_skill(): void
    {
        $this->attempt->update([
            'reading_status' => ['status' => 'attempted'],
            'listening_status' => ['status' => 'attempted'],
        ]);

        $this->answerSkill('reading');

        $offered = $this->offered();

        $this->assertArrayNotHasKey('reading_status', $offered, 'answered work must not be offered');
        $this->assertArrayHasKey(
            'listening_status',
            $offered,
            'listening auto-submitted with nothing recorded, so it is the one an admin needs to reopen'
        );
    }

    /**
     * An expired window is offered whatever else is on the exam: the student
     * never got to finish it.
     */
    public function test_an_expired_skill_is_offered_even_when_another_skill_was_answered(): void
    {
        $this->attempt->update([
            'reading_status' => ['status' => 'attempted'],
            'listening_status' => ['status' => 'expired'],
        ]);

        $this->answerSkill('reading');

        $this->assertArrayHasKey('listening_status', $this->offered());
    }

    /**
     * A window that ran out while the student was away still reads
     * unattempted, because the status is only rewritten when the exam is next
     * opened.
     */
    public function test_a_lapsed_window_still_reading_unattempted_is_offered(): void
    {
        $this->attempt->update([
            'listening_status' => [
                'status' => 'unattempted',
                'start_time' => now()->subHours(3)->format('Y-m-d H:i:s'),
                'end_time' => now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
        ]);

        $this->assertArrayHasKey('listening_status', $this->offered());
    }

    /**
     * A skill still inside its window is left alone: the student can walk back
     * into it without an admin doing anything.
     */
    public function test_a_skill_still_inside_its_window_is_not_offered(): void
    {
        $this->attempt->update([
            'listening_status' => [
                'status' => 'unattempted',
                'start_time' => now()->subMinutes(5)->format('Y-m-d H:i:s'),
                'end_time' => now()->addMinutes(25)->format('Y-m-d H:i:s'),
            ],
        ]);

        $this->assertArrayNotHasKey('listening_status', $this->offered());
    }

    /**
     * The point of the whole feature: a student locked out by a window that
     * ran out is able to sit the paper again afterwards.
     */
    public function test_a_reopened_student_can_sit_the_paper_again(): void
    {
        $admin = Admin::query()->first();

        if ($admin === null) {
            $this->markTestSkipped('Need an admin.');
        }

        config(['admin_permissions' => ['results' => ['view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1]]]);

        $this->attempt->update([
            'reading_status' => [
                'status' => 'unattempted',
                'start_time' => now()->subHours(3)->format('Y-m-d H:i:s'),
                'end_time' => now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
        ]);

        // Locked out: the window closed while they were away.
        $this->actingAs($this->student, 'web')
            ->get('/student/exams/reading-exam')
            ->assertRedirect(route('student.exams'));

        $admin->school_id = null;
        $this->actingAs($admin, 'admin');
        Livewire::test(AttemptedExams::class)->call('reopen', $this->attempt->id);

        $this->assertSame([], $this->attempt->fresh()->reading_status, 'the window should be cleared');

        // And back in, on a fresh clock.
        $this->actingAs($this->student, 'web')
            ->get('/student/exams/reading-exam')
            ->assertOk();

        $reopened = $this->attempt->fresh()->reading_status;
        $this->assertSame('unattempted', $reopened['status'] ?? null);
        $this->assertTrue(
            now()->lt(Carbon::parse($reopened['end_time'])),
            'the new window should be in the future'
        );
    }

    /**
     * The list works the map out once for the page. Asking per row would be a
     * query per row, twenty to a page.
     */
    public function test_the_list_does_not_query_per_row(): void
    {
        $admin = Admin::query()->first();

        if ($admin === null) {
            $this->markTestSkipped('Need an admin.');
        }

        config(['admin_permissions' => ['results' => ['view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1]]]);
        $admin->school_id = null;
        $this->actingAs($admin, 'admin');

        DB::enableQueryLog();
        DB::flushQueryLog();

        Livewire::test(AttemptedExams::class)->assertOk();

        $log = DB::getQueryLog();
        DB::disableQueryLog();

        $counts = [];
        foreach ($log as $query) {
            $counts[$query['query']] = ($counts[$query['query']] ?? 0) + 1;
        }
        arsort($counts);
        $worst = array_key_first($counts);

        $this->assertLessThanOrEqual(
            4,
            $counts[$worst],
            sprintf('the list ran one statement %d times:
%s', $counts[$worst], $worst)
        );
    }
}
