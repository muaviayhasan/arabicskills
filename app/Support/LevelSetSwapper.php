<?php

namespace App\Support;

use App\Models\Level;

class LevelSetSwapper
{
    private const SET_PATTERN = '/SET\h*\(\h*([12])\h*\)/iu';

    /**
     * @var array<int, int>|null
     */
    private static ?array $swapMap = null;

    /**
     * @return array<int, int>
     */
    public static function swapMap(): array
    {
        if (self::$swapMap !== null) {
            return self::$swapMap;
        }

        $map = [];
        $labelToLevelId = [];

        foreach (Level::query()->get(['id', 'legacy_labels']) as $level) {
            foreach ($level->legacy_labels ?? [] as $label) {
                $normalized = preg_replace('/\s+/', '', strtoupper(trim((string) $label)));
                if ($normalized !== '') {
                    $labelToLevelId[$normalized] = (int) $level->id;
                }
            }
        }

        foreach (Level::query()->get(['id', 'legacy_labels']) as $level) {
            foreach ($level->legacy_labels ?? [] as $label) {
                if (! preg_match(self::SET_PATTERN, $label)) {
                    continue;
                }

                $swappedLabel = preg_replace_callback(self::SET_PATTERN, function (array $matches) {
                    $setNumber = trim((string) ($matches[1] ?? ''));

                    return $setNumber === '1' ? 'SET ( 2 )' : 'SET ( 1 )';
                }, $label);

                $normalizedSwapped = preg_replace('/\s+/', '', strtoupper(trim($swappedLabel)));
                $targetId = $labelToLevelId[$normalizedSwapped] ?? null;

                if ($targetId !== null && $targetId !== (int) $level->id) {
                    $map[(int) $level->id] = $targetId;
                }

                break;
            }
        }

        self::$swapMap = $map;

        return $map;
    }

    public static function swappedLevelId(?int $levelId): ?int
    {
        if ($levelId === null) {
            return null;
        }

        return self::swapMap()[$levelId] ?? null;
    }

    public static function hasSetOne(?int $levelId): bool
    {
        return self::setNumber($levelId) === 1;
    }

    public static function hasSetTwo(?int $levelId): bool
    {
        return self::setNumber($levelId) === 2;
    }

    public static function hasSwappableSet(?int $levelId): bool
    {
        return self::setNumber($levelId) !== null;
    }

    private static function setNumber(?int $levelId): ?int
    {
        if ($levelId === null) {
            return null;
        }

        $level = Level::query()->find($levelId);

        if ($level === null) {
            return null;
        }

        foreach ($level->legacy_labels ?? [] as $label) {
            if (preg_match(self::SET_PATTERN, $label, $matches)) {
                $setNumber = (int) trim((string) ($matches[1] ?? ''));

                return in_array($setNumber, [1, 2], true) ? $setNumber : null;
            }
        }

        return null;
    }
}
