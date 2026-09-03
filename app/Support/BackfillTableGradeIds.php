<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackfillTableGradeIds
{
    /**
     * Tables to remap grade_id → canonical numbered grade (excluding exams/activities — migrated separately).
     *
     * @param  array<int, array{table: string, level_column: string|null}>  $tables
     */
    public function __construct(
        private array $tables = [],
    ) {
        if ($this->tables === []) {
            $this->tables = self::defaultTables();
        }
    }

    /**
     * @return array<int, array{table: string, level_column: string|null}>
     */
    public static function defaultTables(): array
    {
        return [
            ['table' => 'sections', 'level_column' => null],
        ];
    }

    public function run(int $chunkSize = 500): array
    {
        $gradeResolver = GradeLegacyResolver::make();
        $reports = [];

        foreach ($this->tables as $config) {
            $table = $config['table'];
            $levelColumn = $config['level_column'] ?? null;

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'grade_id')) {
                continue;
            }

            $reports[$table] = $this->runTable($table, $levelColumn, $gradeResolver, $chunkSize);
        }

        $this->writeReport($reports);
        Log::info('BackfillTableGradeIds completed', $reports);

        return $reports;
    }

    protected function runTable(
        string $table,
        ?string $levelColumn,
        GradeLegacyResolver $gradeResolver,
        int $chunkSize
    ): array {
        $report = [
            'grade_updated' => 0,
            'grade_updated_via_map' => 0,
            'grade_updated_via_attached_grade' => 0,
            'grade_already_canonical' => 0,
            'grade_unmatched' => 0,
            'grade_null_skipped' => 0,
            'sample_grade_unmatched' => [],
        ];

        DB::table($table)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use ($table, $levelColumn, $gradeResolver, &$report) {
                foreach ($rows as $row) {
                    if ($row->grade_id === null) {
                        $report['grade_null_skipped']++;

                        continue;
                    }

                    $originalGradeId = (int) $row->grade_id;
                    $levelString = $levelColumn !== null
                        ? trim((string) ($row->{$levelColumn} ?? ''))
                        : '';

                    $resolvedGradeId = $gradeResolver->resolveForExam($originalGradeId, $levelString);

                    if ($resolvedGradeId === null) {
                        $report['grade_unmatched']++;

                        if (count($report['sample_grade_unmatched']) < 50) {
                            $sample = [
                                'id' => $row->id,
                                'grade_id' => $originalGradeId,
                                'level' => $levelString !== '' ? $levelString : null,
                            ];

                            if (! in_array($sample, $report['sample_grade_unmatched'], true)) {
                                $report['sample_grade_unmatched'][] = $sample;
                            }
                        }

                        continue;
                    }

                    if ($originalGradeId === $resolvedGradeId) {
                        $report['grade_already_canonical']++;

                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['grade_id' => $resolvedGradeId]);

                    $report['grade_updated']++;

                    if ($gradeResolver->wasMappedFromOldGrade($originalGradeId, $resolvedGradeId)) {
                        $report['grade_updated_via_map']++;
                    } else {
                        $report['grade_updated_via_attached_grade']++;
                    }
                }
            });

        return $report;
    }

    protected function writeReport(array $reports): void
    {
        $path = storage_path('logs/backfill-table-grade-ids-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
