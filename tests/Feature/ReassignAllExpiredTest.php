<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamCheck\AttemptedExams;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentExam;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Re-assign All Expired Attempts" hands papers back to every student at
 * once. It used to fire straight off the click: no confirmation, no
 * permission check, and no school scope at all, so an admin tied to one
 * school reset expired skills for every school in the system.
 */
class ReassignAllExpiredTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    private School $school;

    private Grade $grade;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Admin::query()->first();
        $school = School::query()->orderBy('id')->first();
        $grade = Grade::query()->whereNotNull('number')->orderBy('number')->first();
        $level = Level::query()->orderBy('number')->first();

        if ($admin === null || $school === null || $grade === null || $level === null) {
            $this->markTestSkipped('Need an admin, a school, a numbered grade and a level.');
        }

        $this->admin = $admin;
        $this->school = $school;
        $this->grade = $grade;
        $this->level = $level;

        config(['admin_permissions' => ['results' => ['view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1]]]);
    }

    private function actAs(?int $schoolId): void
    {
        $this->admin->school_id = $schoolId;
        $this->actingAs($this->admin, 'admin');
    }

    private function expiredAttempt(?int $schoolId = null): StudentExam
    {
        $reg = 'ZR'.random_int(100000, 999999);

        $student = Student::create([
            'name' => 'Bulk Reassign Child',
            'school_id' => $schoolId ?? $this->school->id,
            'grade_id' => $this->grade->id,
            'level_id' => $this->level->id,
            'registration' => $reg,
            'user_name' => 'AST-Y26-'.$reg,
            'password' => 'x',
            'year' => date('Y'),
        ]);

        $exam = Exam::create([
            'school_id' => $schoolId ?? $this->school->id,
            'grade_id' => $this->grade->id,
            'level_ids' => [$this->level->id],
            'term' => 'Bulk Reassign Round',
            'status' => 'active',
        ]);

        return StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
            'reading_status' => [
                'status' => 'expired',
                'start_time' => Carbon::now()->subHours(3)->format('Y-m-d H:i:s'),
                'end_time' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function test_it_asks_before_touching_anything(): void
    {
        $attempt = $this->expiredAttempt();
        $this->actAs(null);

        Livewire::test(AttemptedExams::class)
            ->call('confirmReassignAllExpired')
            ->assertDispatched('confirmReopen');

        $this->assertSame(
            'expired',
            $attempt->fresh()->reading_status['status'] ?? null,
            'asking must not change anything on its own'
        );
    }

    public function test_confirming_hands_the_expired_skills_back(): void
    {
        $attempt = $this->expiredAttempt();
        $this->actAs(null);

        Livewire::test(AttemptedExams::class)->call('reassignAllExpired');

        $this->assertSame([], $attempt->fresh()->reading_status);
    }

    public function test_it_refuses_without_permission_to_edit_results(): void
    {
        $attempt = $this->expiredAttempt();
        $this->actAs(null);

        config(['admin_permissions' => ['results' => ['view' => 1, 'add' => 0, 'edit' => 0, 'delete' => 0]]]);

        Livewire::test(AttemptedExams::class)
            ->call('confirmReassignAllExpired')
            ->assertNotDispatched('confirmReopen');

        Livewire::test(AttemptedExams::class)->call('reassignAllExpired');

        $this->assertSame(
            'expired',
            $attempt->fresh()->reading_status['status'] ?? null,
            'a view-only admin must not be able to sweep, even by dispatching the event directly'
        );
    }

    /**
     * The list only needs results,view to open, so the sweep cannot rely on
     * the route to keep a view-only admin out.
     */
    public function test_the_sweep_is_scoped_to_the_admins_own_school(): void
    {
        $other = School::query()->where('id', '!=', $this->school->id)->first();

        if ($other === null) {
            $this->markTestSkipped('Need a second school.');
        }

        $mine = $this->expiredAttempt($this->school->id);
        $theirs = $this->expiredAttempt($other->id);

        $this->actAs($this->school->id);

        Livewire::test(AttemptedExams::class)->call('reassignAllExpired');

        $this->assertSame([], $mine->fresh()->reading_status, 'my school should be handed back');
        $this->assertSame(
            'expired',
            $theirs->fresh()->reading_status['status'] ?? null,
            'another school must be left alone'
        );
    }

    public function test_an_admin_over_every_school_sweeps_them_all(): void
    {
        $other = School::query()->where('id', '!=', $this->school->id)->first();

        if ($other === null) {
            $this->markTestSkipped('Need a second school.');
        }

        $mine = $this->expiredAttempt($this->school->id);
        $theirs = $this->expiredAttempt($other->id);

        $this->actAs(null);

        Livewire::test(AttemptedExams::class)->call('reassignAllExpired');

        $this->assertSame([], $mine->fresh()->reading_status);
        $this->assertSame([], $theirs->fresh()->reading_status);
    }

    public function test_a_skill_that_is_not_expired_is_left_alone(): void
    {
        $attempt = $this->expiredAttempt();
        $attempt->update(['listening_status' => ['status' => 'attempted']]);

        $this->actAs(null);

        Livewire::test(AttemptedExams::class)->call('reassignAllExpired');

        $this->assertSame([], $attempt->fresh()->reading_status, 'the expired one goes back');
        $this->assertSame(
            'attempted',
            $attempt->fresh()->listening_status['status'] ?? null,
            'answered work is not part of this sweep'
        );
    }

    public function test_a_checked_exam_is_not_swept(): void
    {
        $attempt = $this->expiredAttempt();
        $attempt->update(['checked' => true]);

        $this->actAs(null);

        Livewire::test(AttemptedExams::class)->call('reassignAllExpired');

        $this->assertSame(
            'expired',
            $attempt->fresh()->reading_status['status'] ?? null,
            'a paper already marked is out of scope'
        );
    }
}
