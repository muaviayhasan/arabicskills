<?php

namespace App\Support;

use App\Models\Level;
use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncLegacyLevelsFromOptions
{
    public function run(): array
    {
        $report = [
            'synced' => 0,
            'unparseable' => [],
            'levels_touched' => [],
        ];

        $rawValue = Option::query()->where('key', 'levels')->value('value');
        $labels = @unserialize($rawValue) ?: [];

        if (! is_array($labels)) {
            $labels = [];
        }

        $resolver = LevelLegacyResolver::make();
        $adminId = (int) (DB::table('admins')->orderBy('id')->value('id') ?? 1);
        $touchedLevels = [];

        foreach ($labels as $label) {
            $originalLabel = trim((string) $label);

            if ($originalLabel === '') {
                continue;
            }

            $levelNumber = LevelLabelParser::extractLevelNumber($originalLabel);

            if ($levelNumber === null) {
                $report['unparseable'][] = $originalLabel;

                continue;
            }

            $level = Level::firstOrCreate(
                ['number' => $levelNumber],
                [
                    'name' => Level::nameFromNumber($levelNumber),
                    'admin_id' => $adminId,
                    'legacy_labels' => [],
                ]
            );

            $resolver->appendLegacyLabel($level, $originalLabel);

            $touchedLevels[$level->id] = $level->number;
            $report['synced']++;
        }

        $report['levels_touched'] = array_values($touchedLevels);

        $this->writeReport($report);
        Log::info('SyncLegacyLevelsFromOptions completed', $report);

        return $report;
    }

    protected function writeReport(array $report): void
    {
        $path = storage_path('logs/sync-options-levels-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
