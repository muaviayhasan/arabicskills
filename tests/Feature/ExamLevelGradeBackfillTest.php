<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Support\BackfillExamLevelGradeIds;
use App\Support\GradeLegacyResolver;
use App\Support\LevelLabelParser;
use App\Support\LevelLegacyResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExamLevelGradeBackfillTest extends TestCase
{
    use DatabaseTransactions;

    public function test_exams_with_grade_id_use_numbered_grades(): void
    {
        if (! Schema::hasColumn('exams', 'level_ids')) {
            $this->markTestSkipped('exams.level_ids not migrated yet.');
        }

        $invalidCount = DB::table('exams')
            ->join('grades', 'exams.grade_id', '=', 'grades.id')
            ->whereNotNull('exams.grade_id')
            ->whereNull('grades.number')
            ->count();

        $this->assertSame(0, $invalidCount);
    }

    public function test_exams_level_ids_point_to_levels_table(): void
    {
        if (! Schema::hasColumn('exams', 'level_ids')) {
            $this->markTestSkipped('exams.level_ids not migrated yet.');
        }

        $levelIds = Exam::query()
            ->whereNotNull('level_ids')
            ->pluck('level_ids')
            ->flatMap(fn ($ids) => is_array($ids) ? $ids : [])
            ->unique()
            ->filter()
            ->values();

        if ($levelIds->isEmpty()) {
            $this->markTestSkipped('No exam level_ids populated yet.');
        }

        $orphan = $levelIds->diff(Level::query()->pluck('id'));

        $this->assertTrue($orphan->isEmpty(), 'Orphan level ids: '.$orphan->implode(', '));
    }

    public function test_exams_with_nonempty_level_have_level_ids_when_parseable(): void
    {
        if (! Schema::hasColumn('exams', 'level_ids')) {
            $this->markTestSkipped('exams.level_ids not migrated yet.');
        }

        if (! Schema::hasColumn('exams', 'level')) {
            $missing = Exam::query()
                ->whereNotNull('grade_id')
                ->where(function ($q) {
                    $q->whereNull('level_ids')
                        ->orWhereJsonLength('level_ids', 0);
                })
                ->count();

            $this->assertSame(0, $missing, 'Exams with grade but empty level_ids after legacy level column removal.');

            return;
        }

        $unmapped = [];

        Exam::query()
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->select(['id', 'level', 'level_ids'])
            ->lazy()
            ->each(function ($exam) use (&$unmapped) {
                if (LevelLabelParser::extractLevelNumber($exam->level) === null) {
                    return;
                }

                $ids = is_array($exam->level_ids) ? $exam->level_ids : [];

                if ($ids === []) {
                    $unmapped[] = [
                        'exam_id' => $exam->id,
                        'level' => $exam->level,
                    ];
                }
            });

        $this->assertEmpty(
            $unmapped,
            count($unmapped).' parseable exam level string(s) missing level_ids. Sample: '
            .json_encode(array_slice($unmapped, 0, 10), JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_no_exam_points_to_legacy_grade_id_listed_in_old_grades_json(): void
    {
        $legacyGradeIds = Grade::query()
            ->whereNotNull('number')
            ->whereNotNull('old_grades')
            ->pluck('old_grades')
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($legacyGradeIds->isEmpty()) {
            $this->markTestSkipped('No legacy grade ids found in grades.old_grades JSON.');
        }

        $stillOnLegacy = Exam::query()
            ->whereIn('grade_id', $legacyGradeIds)
            ->limit(20)
            ->get(['id', 'grade_id']);

        $this->assertTrue(
            $stillOnLegacy->isEmpty(),
            'Exams still pointing at legacy grade ids listed in old_grades JSON: '
            .$stillOnLegacy->toJson()
        );
    }

    public function test_backfill_resolves_level_from_legacy_labels_map(): void
    {
        $level = Level::query()
            ->whereNotNull('legacy_labels')
            ->whereJsonLength('legacy_labels', '>', 0)
            ->first();

        if ($level === null) {
            $this->markTestSkipped('No levels with legacy_labels found.');
        }

        $legacyLabel = $level->legacy_labels[0];
        $map = (new \App\Support\BackfillLevelIds)->buildLegacyMap();
        $resolver = LevelLegacyResolver::make();

        $this->assertSame($level->id, $resolver->resolveLevelId($legacyLabel, $map));
    }

    public function test_resolve_for_exam_maps_legacy_grade_id_via_old_grades(): void
    {
        $canonical = Grade::query()->whereNotNull('number')->whereNotNull('old_grades')->first();

        if ($canonical === null || empty($canonical->old_grades)) {
            $this->markTestSkipped('No canonical grade with old_grades found.');
        }

        $legacyId = (int) $canonical->old_grades[0];
        $resolver = GradeLegacyResolver::make();

        $this->assertSame($canonical->id, $resolver->resolveForExam($legacyId, ''));
    }

    public function test_backfill_runner_completes_without_exception(): void
    {
        if (! DB::table('admins')->orderBy('id')->value('id')) {
            $this->markTestSkipped('No admins found.');
        }

        if (! Schema::hasColumn('exams', 'level_ids')) {
            $this->markTestSkipped('exams.level_ids not migrated yet.');
        }

        $report = app(BackfillExamLevelGradeIds::class)->run(100);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('grade_updated', $report);
        $this->assertArrayHasKey('level_ids_updated', $report);
    }
}
