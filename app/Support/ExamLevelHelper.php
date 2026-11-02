<?php

namespace App\Support;

use App\Models\Level;

class ExamLevelHelper
{
    private const MEMO_KEY = 'ast.level-names';

    /**
     * Every level, id to name, in number order, read once per request.
     *
     * There are ten rows and they do not change during a request, but
     * levelNamesLabel() is called once per row of a list and used to run two
     * queries each time: a page of twenty attempted exams spent forty queries
     * naming levels, and the exports do the same thing per exported row.
     *
     * Held on the container rather than in a static, so it lasts exactly one
     * request and cannot leak between tests.
     *
     * @return array<int, string>
     */
    private static function levelNames(): array
    {
        $app = app();

        if (! $app->bound(self::MEMO_KEY)) {
            $app->instance(self::MEMO_KEY, Level::query()
                ->orderBy('number')
                ->pluck('name', 'id')
                ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name])
                ->all());
        }

        return $app->make(self::MEMO_KEY);
    }

    /**
     * All canonical level row ids (Level 1–10).
     *
     * @return list<int>
     */
    public static function allLevelIds(): array
    {
        return array_keys(self::levelNames());
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

        $all = self::levelNames();
        $allIds = array_keys($all);

        if (count($ids) === count($allIds) && array_diff($allIds, $ids) === []) {
            return 'All Levels';
        }

        // Walked in number order, which is the order the memo is read in.
        $names = [];

        foreach ($all as $id => $name) {
            if (in_array($id, $ids, true)) {
                $names[] = $name;
            }
        }

        return $names !== [] ? implode(', ', $names) : 'N/A';
    }
}
