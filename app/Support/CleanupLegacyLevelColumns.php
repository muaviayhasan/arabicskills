<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CleanupLegacyLevelColumns
{
    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $report = [
            'grade_fk_remapped' => $this->remapRemainingGradeForeignKeys(),
            'legacy_grades_deleted' => 0,
            'options_levels_deleted' => false,
        ];

        $report['legacy_grades_deleted'] = DB::table('grades')
            ->whereNull('number')
            ->delete();

        $report['old_grades_pruned'] = $this->pruneStaleOldGradeReferences();

        if (Schema::hasColumn('grades', 'level')) {
            Schema::table('grades', function ($table) {
                $table->dropColumn('level');
            });
        }

        foreach (['students', 'exams', 'activities'] as $tableName) {
            if (Schema::hasColumn($tableName, 'level')) {
                Schema::table($tableName, function ($table) {
                    $table->dropColumn('level');
                });
            }
        }

        $deleted = DB::table('options')->where('key', 'levels')->delete();
        $report['options_levels_deleted'] = $deleted > 0;

        $this->writeReport($report);
        Log::info('CleanupLegacyLevelColumns completed', $report);

        return $report;
    }

    /**
     * Remove deleted legacy grade ids from canonical rows' old_grades JSON.
     */
    protected function pruneStaleOldGradeReferences(): int
    {
        $existingIds = DB::table('grades')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $existingLookup = array_fill_keys($existingIds, true);
        $updated = 0;

        DB::table('grades')
            ->whereNotNull('old_grades')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$updated, $existingLookup) {
                foreach ($rows as $row) {
                    $oldGrades = json_decode($row->old_grades ?? '[]', true);

                    if (! is_array($oldGrades) || $oldGrades === []) {
                        continue;
                    }

                    $pruned = array_values(array_filter($oldGrades, fn ($id) => isset($existingLookup[(int) $id])));

                    if ($pruned !== $oldGrades) {
                        DB::table('grades')
                            ->where('id', $row->id)
                            ->update(['old_grades' => json_encode($pruned)]);

                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * @return array<string, int>
     */
    protected function remapRemainingGradeForeignKeys(): array
    {
        $tables = [
            ['table' => 'students', 'level_column' => 'level'],
            ['table' => 'exams', 'level_column' => 'level'],
            ['table' => 'activities', 'level_column' => 'level'],
            ['table' => 'sections', 'level_column' => null],
        ];

        $backfill = new BackfillTableGradeIds($tables);

        return $backfill->run();
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function writeReport(array $report): void
    {
        $path = storage_path('logs/cleanup-legacy-level-columns-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
