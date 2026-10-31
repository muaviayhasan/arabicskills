<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamCheck\AttemptedExams;
use App\Livewire\Admin\ExamCheck\StudentExamDetails;
use App\Models\Activity;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Whether an admin can actually act on each situation the brief names:
 *
 *   - the assessment time has expired
 *   - a student hits a technical problem during the assessment
 *   - a student is disconnected, or cannot finish, because of a system error
 *
 * and in every case for one student, without touching another student or
 * anything already submitted.
 *
 * The question each test asks is not "is there a Reopen button" but "is there
 * any route at all", because a row with no answers shows no Details link
 * either, and those two together are what decides whether the admin is stuck.
 */
class ReopenCoverageTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    private Student $student;

    private Exam $exam;

    private Activity $reading;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::query()->first();
        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($admin === null || $student === null) {
            $this->markTestSkipped('Need an admin and a placed student.');
        }

        $this->admin = $admin;
        $this->admin->school_id = null;
        $this->student = $student;

        config(['admin_permissions' => ['results' => ['view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1]]]);

        $this->reading = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Coverage Round',
            'title' => 'Reading activity',
            'activity' => 'Body',
            'type' => 'reading',
        ]);

        Question::create([
            'activity_id' => $this->reading->id,
            'question' => 'Q',
            'type' => 'MCQs',
        ]);

        $this->exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Coverage Round',
            'status' => 'active',
            'reading_activities' => [$this->reading->id],
            'reading_time' => '00:40',
        ]);
    }

    private function attempt(array $statuses): StudentExam
    {
        return StudentExam::create(array_merge([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'checked' => false,
        ], $statuses));
    }

    private function openWindow(): array
    {
        return [
            'status' => 'unattempted',
            'start_time' => Carbon::now()->subMinutes(6)->format('Y-m-d H:i:s'),
            'end_time' => Carbon::now()->addMinutes(34)->format('Y-m-d H:i:s'),
        ];
    }

    /** The brief's first case. */
    public function test_an_expired_assessment_can_be_reopened_from_the_list(): void
    {
        $attempt = $this->attempt([
            'reading_status' => [
                'status' => 'expired',
                'start_time' => Carbon::now()->subHours(3)->format('Y-m-d H:i:s'),
                'end_time' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
        ]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(AttemptedExams::class)->call('reopen', $attempt->id);

        $this->assertSame([], $attempt->fresh()->reading_status);
    }

    /**
     * The brief's second and third cases: trouble while the clock is running.
     * Nothing is written until a paper is submitted, so such a row has no
     * answers, and the list therefore offers neither a Reopen button nor a
     * Details link. This records what the admin is left with.
     */
    public function test_a_student_in_trouble_mid_window_has_no_reopen_button(): void
    {
        $attempt = $this->attempt(['reading_status' => $this->openWindow()]);

        $this->actingAs($this->admin, 'admin');

        $skills = Livewire::test(AttemptedExams::class)
            ->instance()
            ->reopenableSkills($attempt->fresh());

        $this->assertSame(
            [],
            $skills,
            'a window that is still open is deliberately not offered: the student can re-enter it'
        );
    }

    /**
     * ... and the row shows no Details link either, because that is tied to
     * the answer count, so the one situation the brief calls out as needing a
     * reopen is the one with no route to it from this page.
     */
    public function test_the_list_offers_a_route_for_a_row_with_no_answers(): void
    {
        $attempt = $this->attempt(['reading_status' => $this->openWindow()]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(AttemptedExams::class)
            ->assertOk()
            ->assertSee(route('admin.student-exam.details', $attempt->id), false);
    }

    /**
     * The details page is the route for anything the list will not touch, so
     * it has to work for an attempt holding no answers at all.
     */
    public function test_the_details_page_opens_for_an_attempt_with_no_answers(): void
    {
        $attempt = $this->attempt(['reading_status' => $this->openWindow()]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(StudentExamDetails::class, ['exam_id' => $attempt->id])->assertOk();
    }

    /**
     * And reassigning there gives the skill back, which is what an admin needs
     * for a student who lost their time to a fault rather than to the clock.
     */
    public function test_reassigning_hands_back_a_skill_whose_window_is_still_open(): void
    {
        $attempt = $this->attempt(['reading_status' => $this->openWindow()]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(StudentExamDetails::class, ['exam_id' => $attempt->id])
            ->call('reassign', 'reading_status');

        $this->assertSame([], $attempt->fresh()->reading_status);
    }

    /**
     * A skill the student partly answered before being disconnected. The list
     * protects it; the details page is where it is handed back, one at a time.
     */
    public function test_an_answered_skill_can_be_handed_back_from_the_details_page(): void
    {
        $attempt = $this->attempt(['reading_status' => ['status' => 'attempted']]);

        TakeExam::create([
            'student_exam_id' => $attempt->id,
            'student_id' => $this->student->id,
            'question_id' => Question::where('activity_id', $this->reading->id)->value('id'),
            'answer' => 'B',
            'type' => 'MCQs',
        ]);

        $this->actingAs($this->admin, 'admin');

        Livewire::test(StudentExamDetails::class, ['exam_id' => $attempt->id])
            ->call('reassign', 'reading_status');

        $this->assertSame([], $attempt->fresh()->reading_status);

        // The brief is explicit that submitted responses must survive.
        $this->assertSame(1, TakeExam::where('student_exam_id', $attempt->id)->count());
        $this->assertSame('B', TakeExam::where('student_exam_id', $attempt->id)->value('answer'));
    }

    /**
     * One student, not the room: the brief's fourth requirement.
     */
    public function test_reopening_one_student_leaves_the_others_alone(): void
    {
        $other = Student::query()->where('id', '!=', $this->student->id)->first();

        if ($other === null) {
            $this->markTestSkipped('Need a second student.');
        }

        $mine = $this->attempt([
            'reading_status' => [
                'status' => 'expired',
                'start_time' => Carbon::now()->subHours(3)->format('Y-m-d H:i:s'),
                'end_time' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
        ]);

        $theirs = StudentExam::create([
            'student_id' => $other->id,
            'exam_id' => $this->exam->id,
            'checked' => false,
            'reading_status' => [
                'status' => 'expired',
                'start_time' => Carbon::now()->subHours(3)->format('Y-m-d H:i:s'),
                'end_time' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
        ]);

        TakeExam::create([
            'student_exam_id' => $theirs->id,
            'student_id' => $other->id,
            'question_id' => Question::where('activity_id', $this->reading->id)->value('id'),
            'answer' => 'kept',
            'type' => 'MCQs',
        ]);

        $this->actingAs($this->admin, 'admin');
        Livewire::test(AttemptedExams::class)->call('reopen', $mine->id);

        $this->assertSame([], $mine->fresh()->reading_status);
        $this->assertSame('expired', $theirs->fresh()->reading_status['status'] ?? null);
        $this->assertSame('kept', TakeExam::where('student_exam_id', $theirs->id)->value('answer'));
    }
}
