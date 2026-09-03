<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackfillStudentGradeIds
{
    public function run(int $chunkSize = 500): array
    {
        $levelResolver = LevelLegacyResolver::make();
        $gradeResolver = GradeLegacyResolver::make();
        $levelMap = $this->buildLevelLegacyMap();

        $report = [
            'grade_updated' => 0,
            'grade_updated_via_map' => 0,
            'grade_updated_via_year_parse' => 0,
            'grade_already_canonical' => 0,
            'grade_unmatched' => 0,
            'level_updated' => 0,
            'level_unmatched' => 0,
            'sample_grade_unmatched' => [],
            'sample_level_unmatched' => [],
        ];

        DB::table('students')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($levelResolver, $gradeResolver, &$levelMap, &$report) {
                foreach ($rows as $row) {
                    $updates = [];

                    $legacyLevel = trim((string) ($row->level ?? ''));

                    if ($legacyLevel !== '') {
                        $levelId = $levelResolver->resolveLevelId($legacyLevel, $levelMap);

                        if ($levelId !== null) {
                            if ((int) ($row->level_id ?? 0) !== $levelId) {
                                $updates['level_id'] = $levelId;
                                $report['level_updated']++;
                            }
                        } elseif ($row->level_id === null) {
                            $report['level_unmatched']++;

                            if (count($report['sample_level_unmatched']) < 50 && ! in_array($legacyLevel, $report['sample_level_unmatched'], true)) {
                                $report['sample_level_unmatched'][] = $legacyLevel;
                            }
                        }
                    }

                    $originalGradeId = $row->grade_id !== null ? (int) $row->grade_id : null;
                    $resolvedGradeId = $gradeResolver->resolveGradeId($originalGradeId, $legacyLevel);

                    if ($resolvedGradeId !== null) {
                        if ((int) ($row->grade_id ?? 0) !== $resolvedGradeId) {
                            $updates['grade_id'] = $resolvedGradeId;
                            $report['grade_updated']++;

                            if ($gradeResolver->wasMappedFromOldGrade($originalGradeId, $resolvedGradeId)) {
                                $report['grade_updated_via_map']++;
                            } elseif ($originalGradeId === null) {
                                $report['grade_updated_via_year_parse']++;
                            }
                        } else {
                            $report['grade_already_canonical']++;
                        }
                    } elseif ($originalGradeId === null) {
                        $report['grade_unmatched']++;

                        if ($legacyLevel !== '' && count($report['sample_grade_unmatched']) < 50 && ! in_array($legacyLevel, $report['sample_grade_unmatched'], true)) {
                            $report['sample_grade_unmatched'][] = $legacyLevel;
                        }
                    }

                    if ($updates !== []) {
                        DB::table('students')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });

        $this->writeReport($report);
        Log::info('BackfillStudentGradeIds completed', $report);

        return $report;
    }

    protected function buildLevelLegacyMap(): array
    {
        $backfill = app(BackfillLevelIds::class);

        return $backfill->buildLegacyMap();
    }

    protected function writeReport(array $report): void
    {
        $path = storage_path('logs/backfill-students-grade-id-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
