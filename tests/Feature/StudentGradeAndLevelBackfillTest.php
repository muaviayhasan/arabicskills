<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Level;
use App\Models\Student;
use App\Support\GradeLabelParser;
use App\Support\LevelLabelParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentGradeAndLevelBackfillTest extends TestCase
{
    /**
     * Uses the configured database (production import). Does not refresh schema.
     */
    public function test_all_student_grade_ids_point_to_grades_with_number(): void
    {
        $invalid = DB::table('students')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->whereNotNull('students.grade_id')
            ->whereNull('grades.number')
            ->select('students.id', 'students.grade_id', 'grades.name as grade_name')
            ->limit(20)
            ->get();

        $invalidCount = DB::table('students')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->whereNotNull('students.grade_id')
            ->whereNull('grades.number')
            ->count();

        $this->assertSame(
            0,
            $invalidCount,
            'Students with grade_id pointing to legacy grades (number IS NULL): '
            .$invalid->toJson()
        );
    }

    public function test_students_with_grade_id_use_only_numbered_grades(): void
    {
        $withGrade = DB::table('students')->whereNotNull('grade_id')->count();
        $canonical = DB::table('students')
            ->join('grades', 'students.grade_id', '=', 'grades.id')
            ->whereNotNull('students.grade_id')
            ->whereNotNull('grades.number')
            ->count();

        $this->assertSame(
            $withGrade,
            $canonical,
            "Not all assigned grade_id values belong to numbered grades ({$canonical}/{$withGrade})."
        );
    }

    public function test_no_student_points_to_legacy_grade_id_listed_in_old_grades_json(): void
    {
        $legacyGradeIds = $this->legacyGradeIdsFromOldGradesJson();

        if ($legacyGradeIds->isEmpty()) {
            $this->markTestSkipped('No legacy grade ids found in grades.old_grades JSON.');
        }

        $stillOnLegacy = Student::query()
            ->whereIn('grade_id', $legacyGradeIds)
            ->limit(20)
            ->get(['id', 'grade_id']);

        $this->assertTrue(
            $stillOnLegacy->isEmpty(),
            'Students still pointing at legacy grade ids listed in old_grades JSON: '
            .$stillOnLegacy->toJson()
        );
    }

    public function test_canonical_grade_old_grades_json_maps_legacy_rows_to_same_year_number(): void
    {
        $invalid = [];

        Grade::query()
            ->whereNotNull('number')
            ->whereNotNull('old_grades')
            ->get(['id', 'number', 'name', 'old_grades'])
            ->each(function (Grade $canonical) use (&$invalid) {
                foreach ($canonical->old_grades ?? [] as $legacyGradeId) {
                    $legacyGrade = Grade::query()->find($legacyGradeId);

                    if (! $legacyGrade) {
                        continue;
                    }

                    $legacyYear = GradeLabelParser::extractYearNumber($legacyGrade->name);

                    if ($legacyYear !== (int) $canonical->number) {
                        $invalid[] = [
                            'canonical_grade_id' => $canonical->id,
                            'canonical_number' => $canonical->number,
                            'legacy_grade_id' => $legacyGradeId,
                            'legacy_grade_name' => $legacyGrade->name,
                            'legacy_year_parsed' => $legacyYear,
                            'reason' => 'legacy grade name does not map to canonical number',
                        ];
                    }
                }
            });

        $this->assertEmpty(
            $invalid,
            'Invalid grades.old_grades JSON mappings: '
            .json_encode(array_slice($invalid, 0, 10), JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_student_grade_number_matches_year_from_level_when_both_present(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('students', 'level')) {
            $this->assertTrue(true, 'Legacy students.level column removed.');

            return;
        }

        $mismatched = [];

        Student::query()
            ->with('Grade')
            ->whereNotNull('grade_id')
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->lazy()
            ->each(function (Student $student) use (&$mismatched) {
                $yearFromLevel = LevelLabelParser::extractYearNumber($student->level);

                if ($yearFromLevel === null || ! $student->Grade?->number) {
                    return;
                }

                if ((int) $student->Grade->number !== $yearFromLevel) {
                    $mismatched[] = [
                        'student_id' => $student->id,
                        'level' => $student->level,
                        'grade_id' => $student->grade_id,
                        'grade_number' => $student->Grade->number,
                        'year_from_level' => $yearFromLevel,
                    ];
                }
            });

        $this->assertEmpty(
            $mismatched,
            'Students whose grade.number does not match YEAR parsed from level: '
            .json_encode(array_slice($mismatched, 0, 10), JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_student_level_exists_in_legacy_labels_json_column(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('students', 'level')) {
            $this->assertTrue(true, 'Legacy students.level column removed; level_id FK is source of truth.');

            return;
        }

        $missingJson = [];
        $missingNormalized = [];

        Student::query()
            ->whereNotNull('level_id')
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->select(['id', 'level_id', 'level'])
            ->lazy()
            ->each(function ($student) use (&$missingJson, &$missingNormalized) {
                $exactJsonMatch = Level::query()
                    ->whereKey($student->level_id)
                    ->whereJsonContains('legacy_labels', $student->level)
                    ->exists();

                if ($exactJsonMatch) {
                    return;
                }

                $missingJson[] = [
                    'student_id' => $student->id,
                    'level_id' => $student->level_id,
                    'level' => $student->level,
                ];

                $level = Level::query()->find($student->level_id);

                if (! $level || ! $this->levelStringExistsInLegacyLabelsNormalized($student->level, $level)) {
                    $missingNormalized[] = [
                        'student_id' => $student->id,
                        'level_id' => $student->level_id,
                        'level' => $student->level,
                        'legacy_labels' => $level?->legacy_labels,
                    ];
                }
            });

        $this->assertEmpty(
            $missingNormalized,
            count($missingNormalized).' student(s) level string not found in assigned level legacy_labels (JSON/normalized). '
            .'Exact whereJsonContains misses: '.count($missingJson).'. Sample: '
            .json_encode(array_slice($missingNormalized, 0, 10), JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_students_with_nonempty_level_have_level_id_when_parseable(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('students', 'level')) {
            $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('students', 'level'));

            return;
        }

        $unmapped = [];

        Student::query()
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->whereNull('level_id')
            ->select(['id', 'level'])
            ->lazy()
            ->each(function ($student) use (&$unmapped) {
                if (LevelLabelParser::extractLevelNumber($student->level) !== null) {
                    $unmapped[] = [
                        'student_id' => $student->id,
                        'level' => $student->level,
                    ];
                }
            });

        $this->assertEmpty(
            $unmapped,
            count($unmapped).' parseable level string(s) missing level_id. Sample: '
            .json_encode(array_slice($unmapped, 0, 10), JSON_UNESCAPED_UNICODE)
        );
    }

    protected function legacyGradeIdsFromOldGradesJson(): Collection
    {
        return Grade::query()
            ->whereNotNull('number')
            ->whereNotNull('old_grades')
            ->pluck('old_grades')
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    protected function levelStringExistsInLegacyLabelsNormalized(string $studentLevel, Level $level): bool
    {
        $labels = $level->legacy_labels ?? [];

        if ($labels === []) {
            return false;
        }

        $normalizedStudentLevel = LevelLabelParser::normalize($studentLevel);

        foreach ($labels as $label) {
            if ($label === $studentLevel) {
                return true;
            }

            if (LevelLabelParser::normalize($label) === $normalizedStudentLevel) {
                return true;
            }
        }

        return false;
    }
}
