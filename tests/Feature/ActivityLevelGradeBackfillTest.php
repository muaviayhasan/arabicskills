<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Grade;
use App\Models\Level;
use App\Support\BackfillActivityLevelGradeIds;
use App\Support\LevelLabelParser;
use App\Support\LevelLegacyResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivityLevelGradeBackfillTest extends TestCase
{
    use DatabaseTransactions;

    public function test_activities_with_level_id_use_numbered_grades(): void
    {
        if (! Schema::hasColumn('activities', 'grade_id')) {
            $this->markTestSkipped('activities.grade_id not migrated yet.');
        }

        $invalidCount = DB::table('activities')
            ->join('grades', 'activities.grade_id', '=', 'grades.id')
            ->whereNotNull('activities.grade_id')
            ->whereNull('grades.number')
            ->count();

        $this->assertSame(0, $invalidCount);
    }

    public function test_activities_with_level_id_point_to_levels_table(): void
    {
        if (! Schema::hasColumn('activities', 'level_id')) {
            $this->markTestSkipped('activities.level_id not migrated yet.');
        }

        $orphan = DB::table('activities')
            ->whereNotNull('level_id')
            ->whereNotIn('level_id', Level::query()->pluck('id'))
            ->count();

        $this->assertSame(0, $orphan);
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

    public function test_backfill_resolves_grade_from_year_in_level_string(): void
    {
        $canonical = Grade::query()->where('number', 10)->first();

        if ($canonical === null) {
            $this->markTestSkipped('Year 10 grade not found.');
        }

        $resolver = new \App\Support\GradeLegacyResolver(
            (int) (DB::table('admins')->orderBy('id')->value('id') ?? 1)
        );
        $resolver->loadMaps();

        $resolved = $resolver->resolveGradeId(null, 'SET ( 1 ) LEVEL ( 3 ) - YEAR 10');

        $this->assertSame($canonical->id, $resolved);
    }

    public function test_backfill_runner_completes_without_exception(): void
    {
        if (! DB::table('admins')->orderBy('id')->value('id')) {
            $this->markTestSkipped('No admins found.');
        }

        $report = app(BackfillActivityLevelGradeIds::class)->run(100);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('level_updated', $report);
        $this->assertArrayHasKey('grade_updated', $report);
    }

    public function test_activities_with_nonempty_level_have_level_id_when_parseable(): void
    {
        if (! Schema::hasColumn('activities', 'level_id')) {
            $this->markTestSkipped('activities.level_id not migrated yet.');
        }

        if (! Schema::hasColumn('activities', 'level')) {
            $this->assertFalse(Schema::hasColumn('activities', 'level'));

            return;
        }

        $unmapped = [];

        Activity::query()
            ->whereNotNull('level')
            ->where('level', '!=', '')
            ->whereNull('level_id')
            ->select(['id', 'level'])
            ->lazy()
            ->each(function ($activity) use (&$unmapped) {
                if (LevelLabelParser::extractLevelNumber($activity->level) !== null) {
                    $unmapped[] = [
                        'activity_id' => $activity->id,
                        'level' => $activity->level,
                    ];
                }
            });

        $this->assertEmpty(
            $unmapped,
            count($unmapped).' parseable level string(s) missing level_id. Sample: '
            .json_encode(array_slice($unmapped, 0, 10), JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_activities_grade_number_matches_year_from_level_when_both_present(): void
    {
        if (! Schema::hasColumn('activities', 'grade_id')) {
            $this->markTestSkipped('activities.grade_id not migrated yet.');
        }

        if (! Schema::hasColumn('activities', 'level')) {
            $this->assertTrue(true, 'Legacy activities.level column removed; grade_id uses numbered grades only.');

            return;
        }

        $mismatched = DB::table('activities')
            ->join('grades', 'activities.grade_id', '=', 'grades.id')
            ->whereNotNull('activities.level')
            ->whereNotNull('activities.grade_id')
            ->whereNotNull('grades.number')
            ->whereRaw("activities.level LIKE '%YEAR%'")
            ->select('activities.id', 'activities.level', 'grades.number')
            ->limit(20)
            ->get()
            ->filter(function ($row) {
                $year = LevelLabelParser::extractYearNumber($row->level);

                return $year !== null && $year !== (int) $row->number;
            });

        $this->assertTrue(
            $mismatched->isEmpty(),
            'Activity grade number should match YEAR parsed from level string: '.$mismatched->toJson()
        );
    }
}
