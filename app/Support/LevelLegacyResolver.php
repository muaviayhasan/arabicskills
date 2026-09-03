<?php

namespace App\Support;

use App\Models\Level;
use Illuminate\Support\Facades\DB;

class LevelLegacyResolver
{
    public function __construct(
        private int $adminId,
    ) {}

    public static function make(): self
    {
        $adminId = (int) (DB::table('admins')->orderBy('id')->value('id') ?? 1);

        return new self($adminId);
    }

    /**
     * Resolve a level id from a legacy string using direct map lookup, then parsed number fallback.
     */
    public function resolveLevelId(string $legacyLevel, array &$map): ?int
    {
        $legacyLevel = trim($legacyLevel);

        if ($legacyLevel === '') {
            return null;
        }

        $key = LevelLabelParser::normalize($legacyLevel);

        if (isset($map[$key])) {
            return $map[$key];
        }

        $levelNumber = LevelLabelParser::extractLevelNumber($legacyLevel);

        if ($levelNumber === null) {
            return null;
        }

        $level = Level::firstOrCreate(
            ['number' => $levelNumber],
            [
                'name' => Level::nameFromNumber($levelNumber),
                'admin_id' => $this->adminId,
                'legacy_labels' => [],
            ]
        );

        $this->appendLegacyLabel($level, $legacyLevel);
        $map[$key] = $level->id;

        return $level->id;
    }

    /**
     * Resolve level id from legacy strings or canonical import values (number / "Level N").
     */
    public function resolveFromImportValue(string $value, array &$map): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $resolved = $this->resolveLevelId($value, $map);

        if ($resolved !== null) {
            return $resolved;
        }

        $levelNumber = LevelLabelParser::extractCanonicalLevelNumber($value);

        if ($levelNumber === null) {
            return null;
        }

        $level = Level::query()->where('number', $levelNumber)->first();

        if ($level === null) {
            $level = Level::firstOrCreate(
                ['number' => $levelNumber],
                [
                    'name' => Level::nameFromNumber($levelNumber),
                    'admin_id' => $this->adminId,
                    'legacy_labels' => [],
                ]
            );
        }

        $this->appendLegacyLabel($level, $value);
        $map[LevelLabelParser::normalize($value)] = $level->id;

        return $level->id;
    }

    /**
     * First legacy label for exam string matching when level_id is assigned manually.
     */
    public static function legacyStringForLevelId(?int $levelId, ?string $existingLevel = null): ?string
    {
        if ($levelId === null) {
            return $existingLevel;
        }

        $level = Level::query()->find($levelId);

        if ($level === null) {
            return $existingLevel;
        }

        $labels = $level->legacy_labels ?? [];

        if ($labels !== []) {
            return $labels[0];
        }

        return $existingLevel;
    }

    /**
     * Store the original legacy label exactly as provided (trimmed only).
     */
    public function appendLegacyLabel(Level $level, string $originalLabel): void
    {
        $originalLabel = trim($originalLabel);

        if ($originalLabel === '') {
            return;
        }

        $labels = $level->legacy_labels ?? [];

        if (in_array($originalLabel, $labels, true)) {
            return;
        }

        $labels[] = $originalLabel;
        $level->legacy_labels = array_values($labels);
        $level->save();
    }
}
