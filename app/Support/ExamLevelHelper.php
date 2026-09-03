<?php

namespace App\Support;

use App\Models\Level;

class ExamLevelHelper
{
    /**
     * All canonical level row ids (Level 1–10).
     *
     * @return list<int>
     */
    public static function allLevelIds(): array
    {
        return Level::query()
            ->orderBy('number')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int|string>|null  $ids
     * @return list<int>
     */
    public static function normalizeLevelIds(?array $ids): array
    {
        if ($ids === null || $ids === []) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => $id !== null && $id !== ''))));
    }

    /**
     * @param  list<int|string>|null  $selected
     * @return list<int>
     */
    public static function resolveLevelIds(?array $selected): array
    {
        return self::normalizeLevelIds($selected);
    }

    /**
     * Human-readable label for exam list / bulk preview.
     *
     * @param  list<int>|null  $levelIds
     */
    public static function levelNamesLabel(?array $levelIds): string
    {
        $ids = self::normalizeLevelIds($levelIds);

        if ($ids === []) {
            return 'N/A';
        }

        $allIds = self::allLevelIds();

        if (count($ids) === count($allIds) && array_diff($allIds, $ids) === []) {
            return 'All Levels';
        }

        $names = Level::query()
            ->whereIn('id', $ids)
            ->orderBy('number')
            ->pluck('name')
            ->all();

        return $names !== [] ? implode(', ', $names) : 'N/A';
    }
}
