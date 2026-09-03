<?php

namespace App\Support;

use App\Models\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackfillLevelIds
{
    public function run(BackfillConfig $config): array
    {
        $resolver = LevelLegacyResolver::make();
        $map = $this->buildLegacyMap();

        if ($config->supplementFromDistinctLevels) {
            $this->supplementMapFromDistinctLevels($config, $resolver, $map);
        }

        $report = [
            'table' => $config->table,
            'updated' => 0,
            'updated_via_map' => 0,
            'updated_via_parse' => 0,
            'skipped_empty' => 0,
            'unmatched' => 0,
            'sample_unmatched' => [],
        ];

        DB::table($config->table)
            ->orderBy($config->idColumn)
            ->chunkById($config->chunkSize, function ($rows) use ($config, $resolver, &$map, &$report) {
                foreach ($rows as $row) {
                    $legacyLevel = trim((string) ($row->{$config->levelStringColumn} ?? ''));

                    if ($legacyLevel === '') {
                        $report['skipped_empty']++;

                        continue;
                    }

                    $lookupKey = LevelLabelParser::normalize($legacyLevel);
                    $hadMapHit = isset($map[$lookupKey]);
                    $levelId = $resolver->resolveLevelId($legacyLevel, $map);

                    if ($levelId === null) {
                        $report['unmatched']++;

                        if (count($report['sample_unmatched']) < 50 && ! in_array($legacyLevel, $report['sample_unmatched'], true)) {
                            $report['sample_unmatched'][] = $legacyLevel;
                        }

                        continue;
                    }

                    DB::table($config->table)
                        ->where($config->idColumn, $row->{$config->idColumn})
                        ->update([$config->levelIdColumn => $levelId]);

                    $report['updated']++;

                    if ($hadMapHit) {
                        $report['updated_via_map']++;
                    } else {
                        $report['updated_via_parse']++;
                    }
                }
            }, $config->idColumn);

        $this->writeReport('backfill-'.$config->table.'-level-id', $report);
        Log::info('BackfillLevelIds completed', $report);

        return $report;
    }

    public function buildLegacyMap(): array
    {
        $map = [];

        Level::query()
            ->whereNotNull('legacy_labels')
            ->get(['id', 'legacy_labels'])
            ->each(function (Level $level) use (&$map) {
                foreach ($level->legacy_labels ?? [] as $label) {
                    $map[LevelLabelParser::normalize($label)] = $level->id;
                }
            });

        return $map;
    }

    protected function supplementMapFromDistinctLevels(BackfillConfig $config, LevelLegacyResolver $resolver, array &$map): void
    {
        DB::table($config->table)
            ->select($config->levelStringColumn)
            ->whereNotNull($config->levelStringColumn)
            ->where($config->levelStringColumn, '!=', '')
            ->distinct()
            ->orderBy($config->levelStringColumn)
            ->pluck($config->levelStringColumn)
            ->each(function ($label) use ($resolver, &$map) {
                $resolver->resolveLevelId((string) $label, $map);
            });
    }

    protected function writeReport(string $prefix, array $report): void
    {
        $path = storage_path('logs/'.$prefix.'-'.now()->format('Y-m-d_His').'.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
