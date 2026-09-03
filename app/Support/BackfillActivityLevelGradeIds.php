<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackfillActivityLevelGradeIds
{
    public function run(int $chunkSize = 500): array
    {
        $levelResolver = LevelLegacyResolver::make();
        $gradeResolver = GradeLegacyResolver::make();
        $levelMap = (new BackfillLevelIds)->buildLegacyMap();

        $report = [
            'level_updated' => 0,
            'grade_updated' => 0,
            'grade_updated_via_year_parse' => 0,
            'level_unmatched' => 0,
            'grade_unmatched' => 0,
            'skipped_empty_level' => 0,
            'sample_level_unmatched' => [],
            'sample_grade_unmatched' => [],
        ];

        DB::table('activities')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($levelResolver, $gradeResolver, &$levelMap, &$report) {
                foreach ($rows as $row) {
                    $legacyLevel = trim((string) ($row->level ?? ''));

                    if ($legacyLevel === '') {
                        $report['skipped_empty_level']++;

                        continue;
                    }

                    $updates = [];

                    $levelId = $levelResolver->resolveLevelId($legacyLevel, $levelMap);

                    if ($levelId !== null) {
                        if ((int) ($row->level_id ?? 0) !== $levelId) {
                            $updates['level_id'] = $levelId;
                            $report['level_updated']++;
                        }
                    } else {
                        $report['level_unmatched']++;

                        if (count($report['sample_level_unmatched']) < 50 && ! in_array($legacyLevel, $report['sample_level_unmatched'], true)) {
                            $report['sample_level_unmatched'][] = $legacyLevel;
                        }
                    }

                    $resolvedGradeId = $gradeResolver->resolveGradeId(null, $legacyLevel);

                    if ($resolvedGradeId !== null) {
                        if ((int) ($row->grade_id ?? 0) !== $resolvedGradeId) {
                            $updates['grade_id'] = $resolvedGradeId;
                            $report['grade_updated']++;
                            $report['grade_updated_via_year_parse']++;
                        }
                    } else {
                        $report['grade_unmatched']++;

                        if (count($report['sample_grade_unmatched']) < 50 && ! in_array($legacyLevel, $report['sample_grade_unmatched'], true)) {
                            $report['sample_grade_unmatched'][] = $legacyLevel;
                        }
                    }

                    if ($updates !== []) {
                        DB::table('activities')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });

        $this->writeReport($report);
        Log::info('BackfillActivityLevelGradeIds completed', $report);

        return $report;
    }

    protected function writeReport(array $report): void
    {
        $path = storage_path('logs/backfill-activities-level-grade-id-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
