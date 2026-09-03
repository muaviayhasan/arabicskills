<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackfillExamLevelGradeIds
{
    public function run(int $chunkSize = 500): array
    {
        $levelResolver = LevelLegacyResolver::make();
        $gradeResolver = GradeLegacyResolver::make();
        $levelMap = (new BackfillLevelIds)->buildLegacyMap();

        $report = [
            'grade_updated' => 0,
            'grade_updated_via_map' => 0,
            'grade_updated_via_year_parse' => 0,
            'grade_updated_via_attached_grade' => 0,
            'grade_already_canonical' => 0,
            'grade_unmatched' => 0,
            'level_ids_updated' => 0,
            'level_ids_resolved' => 0,
            'level_ids_empty' => 0,
            'level_unmatched' => 0,
            'sample_grade_unmatched' => [],
            'sample_level_unmatched' => [],
        ];

        DB::table('exams')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($levelResolver, $gradeResolver, &$levelMap, &$report) {
                foreach ($rows as $row) {
                    $updates = [];
                    $legacyLevel = trim((string) ($row->level ?? ''));

                    $resolvedLevelIds = $this->resolveLevelIds($legacyLevel, $levelResolver, $levelMap, $report);
                    $currentLevelIds = $this->decodeLevelIds($row->level_ids ?? null);

                    if ($resolvedLevelIds !== $currentLevelIds) {
                        $updates['level_ids'] = json_encode(array_values($resolvedLevelIds));
                        $report['level_ids_updated']++;
                    }

                    $originalGradeId = $row->grade_id !== null ? (int) $row->grade_id : null;
                    $resolvedGradeId = $gradeResolver->resolveForExam($originalGradeId, $legacyLevel);

                    if ($resolvedGradeId !== null) {
                        if ((int) ($row->grade_id ?? 0) !== $resolvedGradeId) {
                            $updates['grade_id'] = $resolvedGradeId;
                            $report['grade_updated']++;

                            if ($gradeResolver->wasMappedFromOldGrade($originalGradeId, $resolvedGradeId)) {
                                $report['grade_updated_via_map']++;
                            } elseif ($originalGradeId === null || ! $gradeResolver->wasAlreadyCanonical($originalGradeId, $resolvedGradeId)) {
                                if (LevelLabelParser::extractYearNumber($legacyLevel) !== null) {
                                    $report['grade_updated_via_year_parse']++;
                                } else {
                                    $report['grade_updated_via_attached_grade']++;
                                }
                            }
                        } else {
                            $report['grade_already_canonical']++;
                        }
                    } elseif ($originalGradeId !== null) {
                        $report['grade_unmatched']++;

                        if (count($report['sample_grade_unmatched']) < 50) {
                            $sample = [
                                'exam_id' => $row->id,
                                'grade_id' => $originalGradeId,
                                'level' => $legacyLevel,
                            ];

                            if (! in_array($sample, $report['sample_grade_unmatched'], true)) {
                                $report['sample_grade_unmatched'][] = $sample;
                            }
                        }
                    }

                    if ($updates !== []) {
                        DB::table('exams')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });

        $this->writeReport($report);
        Log::info('BackfillExamLevelGradeIds completed', $report);

        return $report;
    }

    /**
     * @return list<int>
     */
    protected function resolveLevelIds(
        string $legacyLevel,
        LevelLegacyResolver $levelResolver,
        array &$levelMap,
        array &$report
    ): array {
        if ($legacyLevel === '') {
            $report['level_ids_empty']++;

            return [];
        }

        $levelId = $levelResolver->resolveLevelId($legacyLevel, $levelMap);

        if ($levelId !== null) {
            $report['level_ids_resolved']++;

            return [$levelId];
        }

        $report['level_unmatched']++;
        $report['level_ids_empty']++;

        if (count($report['sample_level_unmatched']) < 50 && ! in_array($legacyLevel, $report['sample_level_unmatched'], true)) {
            $report['sample_level_unmatched'][] = $legacyLevel;
        }

        return [];
    }

    /**
     * @return list<int>
     */
    protected function decodeLevelIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (! is_array($decoded)) {
                return [];
            }

            $value = $decoded;
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $value)));
    }

    protected function writeReport(array $report): void
    {
        $path = storage_path('logs/backfill-exams-level-grade-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
