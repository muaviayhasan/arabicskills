<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Level;
use App\Models\Student;
use App\Support\StudentImportRowResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentImportResolutionMatrixTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! DB::table('admins')->orderBy('id')->value('id')) {
            $this->markTestSkipped('No admins found — re-import production DB and run migrate --force.');
        }

        if (Level::query()->where('number', 3)->doesntExist()) {
            $this->markTestSkipped('Level 3 not seeded — run migrations on imported DB.');
        }

        if (Grade::query()->where('number', 10)->doesntExist()) {
            $this->markTestSkipped('Year 10 grade not seeded — run migrations on imported DB.');
        }
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: bool, 3: ?int, 4: ?int}>
     */
    public static function importRowMatrixProvider(): array
    {
        return [
            'both null' => [null, null, false, null, null],
            'both empty strings' => ['', '', false, null, null],
            'new grade number + new level number' => ['10', '3', true, 3, 10],
            'new grade name + new level name' => ['Year 10', 'Level 3', true, 3, 10],
            'legacy level with year + empty grade' => [null, 'SET ( 1 ) LEVEL ( 3 ) - YEAR 10', true, 3, 10],
            'legacy level with year + null grade' => [null, 'SET ( 1 ) LEVEL ( 3 ) - YEAR 10', true, 3, 10],
            'grade only level null' => ['Year 10', null, false, null, 10],
            'grade only level empty' => ['10', '', false, null, 10],
            'level only grade null' => [null, 'Level 3', false, 3, null],
            'level only grade empty' => ['', '3', false, 3, null],
            'new level without year grade empty' => ['', 'Level 5', false, 5, null],
            'unparseable level with valid grade' => ['Year 10', 'A', false, null, 10],
            'unparseable level band' => ['Year 10', 'A+', false, null, 10],
            'both unparseable' => ['Unknown Band', 'A', false, null, null],
            'whitespace grade and level' => ['  Year 10  ', '  Level 3  ', true, 3, 10],
            'numeric grade with legacy level string' => [
                '10',
                'SET ( 2 ) LEVEL ( 3 ) - YEAR 10',
                true,
                3,
                10,
            ],
        ];
    }

    #[DataProvider('importRowMatrixProvider')]
    public function test_import_resolution_matrix(
        ?string $grade,
        ?string $level,
        bool $expectResolvable,
        ?int $expectedLevelNumber,
        ?int $expectedGradeNumber,
    ): void {
        $resolver = new StudentImportRowResolver;

        $resolved = null;

        try {
            $resolved = $resolver->resolve($grade, $level);
        } catch (\Throwable $e) {
            $this->fail('Import resolution threw an exception: '.$e->getMessage());
        }

        $this->assertIsArray($resolved);
        $this->assertArrayHasKey('errors', $resolved);

        if ($expectResolvable) {
            $this->assertTrue($resolver->isResolvable($grade, $level), 'Expected row to be resolvable. Errors: '.implode(' ', $resolved['errors']));
            $this->assertNotNull($resolved['level_id']);
            $this->assertNotNull($resolved['grade_id']);
            $this->assertSame("Level {$expectedLevelNumber}", $resolved['level_name']);
            $this->assertSame("Year {$expectedGradeNumber}", $resolved['grade_name']);
            $this->assertSame([], $resolved['errors']);
        } else {
            $this->assertFalse($resolver->isResolvable($grade, $level));
            $this->assertNotEmpty($resolved['errors']);
        }

        if ($expectedLevelNumber !== null && $resolved['level_id'] !== null) {
            $this->assertSame($expectedLevelNumber, Level::query()->find($resolved['level_id'])?->number);
        } elseif ($expectedLevelNumber === null) {
            $this->assertNull($resolved['level_id']);
        }

        if ($expectedGradeNumber !== null && $resolved['grade_id'] !== null) {
            $this->assertSame($expectedGradeNumber, Grade::query()->find($resolved['grade_id'])?->number);
        } elseif ($expectedGradeNumber === null) {
            $this->assertNull($resolved['grade_id']);
        }
    }

    public function test_import_resolution_never_throws_on_garbage_input(): void
    {
        $resolver = new StudentImportRowResolver;
        $garbageInputs = [
            [null, null],
            ['', ''],
            ["\x00", "\x00"],
            [str_repeat('X', 500), str_repeat('Y', 500)],
            ['<?php', '<script>'],
            ['Year 999', 'Level 999'],
        ];

        foreach ($garbageInputs as [$grade, $level]) {
            try {
                $resolved = $resolver->resolve($grade, $level);
                $this->assertIsArray($resolved);
                $this->assertArrayHasKey('errors', $resolved);
            } catch (\Throwable $e) {
                $this->fail('Garbage input caused exception: grade='.var_export($grade, true).' level='.var_export($level, true).' — '.$e->getMessage());
            }
        }
    }

    public function test_export_uses_assigned_level_name_only(): void
    {
        $student = Student::query()
            ->whereNotNull('level_id')
            ->with('assignedLevel')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('No student with level_id found.');
        }

        $export = new \App\Exports\StudentExport(Student::query()->whereKey($student->id)->with(['School', 'Grade', 'Section', 'assignedLevel']));
        $mapped = $export->map($student->fresh(['assignedLevel', 'Grade', 'Section', 'School']));

        $this->assertSame($student->assignedLevel?->name ?? '', $mapped[7]);
        $this->assertNotEmpty($mapped[7], 'Export must use assigned level name from levels table.');
    }
}
