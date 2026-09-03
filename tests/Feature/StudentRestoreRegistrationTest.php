<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Level;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudentRestoreRegistrationTest extends TestCase
{
    use DatabaseTransactions;

    private function studentFixtures(): array
    {
        $school = School::query()->orderBy('id')->first();
        $grade = Grade::query()->whereNotNull('number')->orderBy('number')->first();
        $level = Level::query()->orderBy('number')->first();

        if ($school === null || $grade === null || $level === null) {
            $this->markTestSkipped('Need school, grade, and level seed data.');
        }

        return compact('school', 'grade', 'level');
    }

    public function test_has_active_registration_conflict_detects_matching_active_student(): void
    {
        $fixtures = $this->studentFixtures();
        $year = (int) date('Y');
        $registration = 'RESTORE-CHK-'.Str::uuid();

        $active = Student::create([
            'school_id' => $fixtures['school']->id,
            'grade_id' => $fixtures['grade']->id,
            'level_id' => $fixtures['level']->id,
            'name' => 'Active Student',
            'registration' => $registration,
            'year' => $year,
            'user_name' => 'active_'.Str::random(8),
            'password' => 'secret',
            'nationality' => 'Test',
            'category' => 'Test',
        ]);

        $archived = Student::create([
            'school_id' => $fixtures['school']->id,
            'grade_id' => $fixtures['grade']->id,
            'level_id' => $fixtures['level']->id,
            'name' => 'Archived Student',
            'registration' => 'OTHER-'.Str::uuid(),
            'year' => $year,
            'user_name' => 'archived_'.Str::random(8),
            'password' => 'secret',
            'nationality' => 'Test',
            'category' => 'Test',
        ]);
        $archived->delete();
        $archived = Student::withTrashed()->find($archived->id);

        $this->assertFalse($archived->hasActiveRegistrationConflict());

        $archived->registration = $active->registration;
        $archived->year = $active->year;

        $this->assertTrue($archived->hasActiveRegistrationConflict());
    }

    public function test_archived_student_can_be_edited_to_new_registration(): void
    {
        $fixtures = $this->studentFixtures();
        $year = (int) date('Y');

        $archived = Student::create([
            'school_id' => $fixtures['school']->id,
            'grade_id' => $fixtures['grade']->id,
            'level_id' => $fixtures['level']->id,
            'name' => 'Archived Student',
            'registration' => 'ARCH-EDIT-'.Str::uuid(),
            'year' => $year,
            'user_name' => 'archived_'.Str::random(8),
            'password' => 'secret',
            'nationality' => 'Test',
            'category' => 'Test',
        ]);
        $archived->delete();

        $newRegistration = 'NEW-REG-'.Str::uuid();
        $archived->registration = $newRegistration;
        $archived->save();

        $this->assertSame($newRegistration, Student::withTrashed()->find($archived->id)->registration);
    }
}
