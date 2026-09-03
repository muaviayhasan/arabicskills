<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Level;
use App\Support\BackfillLevelIds;
use App\Support\GradeLabelParser;
use App\Support\GradeLegacyResolver;
use App\Support\LevelLabelParser;
use App\Support\LevelLegacyResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminStudentLevelCrudTest extends TestCase
{
    use DatabaseTransactions;

    public function test_extract_canonical_level_number_from_plain_and_named_values(): void
    {
        $this->assertSame(3, LevelLabelParser::extractCanonicalLevelNumber('3'));
        $this->assertSame(3, LevelLabelParser::extractCanonicalLevelNumber('Level 3'));
        $this->assertSame(3, LevelLabelParser::extractCanonicalLevelNumber('level 3'));
        $this->assertNull(LevelLabelParser::extractCanonicalLevelNumber('A'));
        $this->assertNull(LevelLabelParser::extractCanonicalLevelNumber('A+'));
    }

    public function test_import_resolves_legacy_level_string(): void
    {
        $level = Level::query()
            ->whereNotNull('legacy_labels')
            ->whereJsonLength('legacy_labels', '>', 0)
            ->first();

        if ($level === null) {
            $this->markTestSkipped('No levels with legacy_labels found in database.');
        }

        $legacyLabel = $level->legacy_labels[0];
        $map = (new BackfillLevelIds)->buildLegacyMap();
        $resolver = LevelLegacyResolver::make();

        $resolved = $resolver->resolveFromImportValue($legacyLabel, $map);

        $this->assertSame($level->id, $resolved);
    }

    public function test_import_resolves_new_level_formats_to_same_level(): void
    {
        $level = Level::query()->where('number', 3)->first();

        if ($level === null) {
            $this->markTestSkipped('Level 3 not found in database.');
        }

        $map = (new BackfillLevelIds)->buildLegacyMap();
        $resolver = LevelLegacyResolver::make();

        $fromNumber = $resolver->resolveFromImportValue('3', $map);
        $fromName = $resolver->resolveFromImportValue('Level 3', $map);

        $this->assertSame($level->id, $fromNumber);
        $this->assertSame($level->id, $fromName);
    }

    public function test_import_creates_missing_level_for_canonical_number(): void
    {
        if (! DB::table('admins')->orderBy('id')->value('id')) {
            $this->markTestSkipped('No admins found — run migrations on the test database.');
        }

        Level::query()->where('number', 7)->delete();

        $this->assertNull(Level::query()->where('number', 7)->first());

        $map = (new BackfillLevelIds)->buildLegacyMap();
        $resolver = LevelLegacyResolver::make();

        $resolved = $resolver->resolveFromImportValue('Level 7', $map);

        $this->assertNotNull($resolved);
        $this->assertSame(7, Level::query()->find($resolved)?->number);
        $this->assertSame('Level 7', Level::query()->find($resolved)?->name);
    }

    public function test_import_unparseable_level_returns_null(): void
    {
        $map = (new BackfillLevelIds)->buildLegacyMap();
        $resolver = LevelLegacyResolver::make();

        $this->assertNull($resolver->resolveFromImportValue('A', $map));
        $this->assertNull($resolver->resolveFromImportValue('A+', $map));
    }

    public function test_import_resolves_grade_from_level_string_year_token(): void
    {
        $canonical = Grade::query()->where('number', 10)->first();

        if ($canonical === null) {
            $this->markTestSkipped('Year 10 grade not found in database.');
        }

        $gradeNameToId = Grade::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Grade $grade) => [
                GradeLabelParser::normalize($grade->name) => $grade->id,
            ])
            ->toArray();

        $resolver = GradeLegacyResolver::make();

        $resolved = $resolver->resolveFromImportValue(
            '',
            'SET ( 1 ) LEVEL ( 3 ) - YEAR 10',
            $gradeNameToId
        );

        $this->assertSame($canonical->id, $resolved);
    }

    public function test_import_resolves_grade_from_year_column(): void
    {
        $canonical = Grade::query()->where('number', 10)->first();

        if ($canonical === null) {
            $this->markTestSkipped('Year 10 grade not found in database.');
        }

        $gradeNameToId = Grade::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Grade $grade) => [
                GradeLabelParser::normalize($grade->name) => $grade->id,
            ])
            ->toArray();

        $resolver = GradeLegacyResolver::make();

        $resolved = $resolver->resolveFromImportValue('Year 10', '', $gradeNameToId);

        $this->assertSame($canonical->id, $resolved);
    }

    public function test_import_creates_missing_canonical_grade(): void
    {
        if (! DB::table('admins')->orderBy('id')->value('id')) {
            $this->markTestSkipped('No admins found — run migrations on the test database.');
        }

        if (! Schema::hasTable('grades')) {
            $this->markTestSkipped('Grades table not found.');
        }

        Grade::query()->where('number', 8)->delete();

        $this->assertNull(Grade::query()->where('number', 8)->first());

        $resolver = GradeLegacyResolver::make();

        $resolved = $resolver->resolveFromImportValue('Year 8', '', []);

        $this->assertNotNull($resolved);
        $this->assertSame(8, Grade::query()->find($resolved)?->number);
        $this->assertSame('Year 8', Grade::query()->find($resolved)?->name);
    }

    public function test_import_does_not_create_unnumbered_legacy_grade(): void
    {
        $before = Grade::query()->whereNull('number')->count();

        $resolver = GradeLegacyResolver::make();
        $resolved = $resolver->resolveFromImportValue('Unknown Band', 'A', []);

        $this->assertNull($resolved);
        $this->assertSame($before, Grade::query()->whereNull('number')->count());
    }
}
