<?php

namespace App\Support;

use App\Models\Grade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncLegacyGradesToOldGrades
{
    public function run(): array
    {
        $report = [
            'synced' => 0,
            'unparseable' => [],
            'grades_touched' => [],
        ];

        $adminId = (int) (DB::table('admins')->orderBy('id')->value('id') ?? 1);
        $touchedNumbers = [];

        Grade::query()
            ->whereNull('number')
            ->orderBy('id')
            ->get()
            ->each(function (Grade $oldGrade) use (&$report, &$touchedNumbers, $adminId) {
                $yearNumber = GradeLabelParser::extractYearNumber($oldGrade->name);

                if ($yearNumber === null) {
                    $report['unparseable'][] = [
                        'id' => $oldGrade->id,
                        'name' => $oldGrade->name,
                    ];

                    return;
                }

                $canonical = Grade::firstOrCreate(
                    ['number' => $yearNumber],
                    [
                        'name' => Grade::nameFromNumber($yearNumber),
                        'admin_id' => $adminId,
                        'old_grades' => [],
                    ]
                );

                $oldGradeIds = $canonical->old_grades ?? [];

                if (! in_array($oldGrade->id, $oldGradeIds, true)) {
                    $oldGradeIds[] = $oldGrade->id;
                    $canonical->old_grades = array_values($oldGradeIds);
                    $canonical->save();
                }

                $touchedNumbers[$canonical->number] = $canonical->id;
                $report['synced']++;
            });

        $report['grades_touched'] = array_keys($touchedNumbers);

        $this->writeReport($report);
        Log::info('SyncLegacyGradesToOldGrades completed', $report);

        return $report;
    }

    protected function writeReport(array $report): void
    {
        $path = storage_path('logs/sync-legacy-grades-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
