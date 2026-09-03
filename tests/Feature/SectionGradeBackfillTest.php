<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SectionGradeBackfillTest extends TestCase
{
    use DatabaseTransactions;

    public function test_sections_with_grade_id_use_numbered_grades(): void
    {
        if (! Schema::hasTable('sections')) {
            $this->markTestSkipped('sections table not found.');
        }

        $invalidCount = DB::table('sections')
            ->join('grades', 'sections.grade_id', '=', 'grades.id')
            ->whereNotNull('sections.grade_id')
            ->whereNull('grades.number')
            ->count();

        $this->assertSame(0, $invalidCount);
    }

    public function test_no_section_points_to_legacy_grade_id_listed_in_old_grades_json(): void
    {
        $legacyGradeIds = DB::table('grades')
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

        $stillOnLegacy = DB::table('sections')
            ->whereIn('grade_id', $legacyGradeIds)
            ->limit(20)
            ->get(['id', 'grade_id']);

        $this->assertTrue(
            $stillOnLegacy->isEmpty(),
            'Sections still pointing at legacy grade ids listed in old_grades JSON: '
            .$stillOnLegacy->toJson()
        );
    }
}
