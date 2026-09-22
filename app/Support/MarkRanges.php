<?php

namespace App\Support;

use App\Models\Option;

/**
 * The mark ranges that decide Below / In line / Above, set by the admin on the
 * Settings page.
 *
 * Each scale is stored as two thresholds: where In line begins and where Above
 * begins. Below is everything under the first. Ranges therefore cannot overlap
 * or leave a gap, and a decimal mark such as 9.5 still falls in exactly one band.
 *
 * Settings are read from the database rather than config('options.*'), which is
 * only filled on web requests and would silently fall back to the defaults in
 * tests, queued jobs and artisan commands.
 */
class MarkRanges
{
    public const SKILL = 'skill';

    public const TOTAL = 'total';

    /** Maximum marks per scale. Fixed by the assessment, not an admin setting. */
    public const MAX = [
        self::SKILL => 20,  // each of the five skills
        self::TOTAL => 100, // the five skills added together
    ];

    public const LABELS = [
        self::SKILL => 'Each skill',
        self::TOTAL => 'Total of all skills',
    ];

    /** The five assessed skills: database column => label. */
    public const SKILLS = [
        'reading' => 'Reading',
        'listening' => 'Listening',
        'writing' => 'Writing',
        'speaking' => 'Speaking',
        'sentences_structures' => 'Sentence Structures',
    ];

    public const ROUNDS = [
        1 => 'Round One',
        2 => 'Round Two',
        3 => 'Round Three',
    ];

    public const BELOW = 'Below';

    public const IN_LINE = 'In line';

    public const ABOVE = 'Above';

    public const DISPLAY_NUMBER = 'number';

    public const DISPLAY_PERCENTAGE = 'percentage';

    /** Option keys, with the defaults from the client's mark range sheet. */
    public const DEFAULTS = [
        'mark_skill_in_line_from' => '10',
        'mark_skill_above_from' => '15',
        'mark_total_in_line_from' => '50',
        'mark_total_above_from' => '75',
        'mark_display' => self::DISPLAY_NUMBER,
    ];

    private static ?array $settings = null;

    /**
     * @return array{in_line: int, above: int, max: int}
     */
    public static function thresholds(string $scale): array
    {
        $settings = self::settings();

        return [
            'in_line' => (int) $settings["mark_{$scale}_in_line_from"],
            'above' => (int) $settings["mark_{$scale}_above_from"],
            'max' => self::MAX[$scale],
        ];
    }

    /**
     * @return list<array{label: string, from: int, to: int}>
     */
    public static function bands(string $scale): array
    {
        $thresholds = self::thresholds($scale);

        return self::bandsFor($thresholds['in_line'], $thresholds['above'], $thresholds['max']);
    }

    /**
     * The three bands the way the client writes them: Below 0–9, In line
     * 10–14, Above 15–20. Pure, so the Settings page can preview values that
     * have not been saved yet.
     *
     * @return list<array{label: string, from: int, to: int}>
     */
    public static function bandsFor(int $inLineFrom, int $aboveFrom, int $max): array
    {
        return [
            ['label' => 'Below', 'from' => 0, 'to' => $inLineFrom - 1],
            ['label' => 'In line', 'from' => $inLineFrom, 'to' => $aboveFrom - 1],
            ['label' => 'Above', 'from' => $aboveFrom, 'to' => $max],
        ];
    }

    /**
     * Whether two thresholds make three non-empty bands. Mirrors rules(), so
     * the live preview never draws a range that saving would reject.
     */
    public static function isValid(mixed $inLineFrom, mixed $aboveFrom, int $max): bool
    {
        if (! self::isWholeNumber($inLineFrom) || ! self::isWholeNumber($aboveFrom)) {
            return false;
        }

        $inLineFrom = (int) $inLineFrom;
        $aboveFrom = (int) $aboveFrom;

        return $inLineFrom >= 1 && $aboveFrom > $inLineFrom && $aboveFrom <= $max;
    }

    /**
     * The judgment for a mark: Below, In line or Above.
     *
     * Compared against the lower bounds, so any mark lands in exactly one band,
     * decimals included. A blank mark has no judgment.
     */
    public static function judge(int|float|string|null $mark, string $scale): ?string
    {
        if ($mark === null || $mark === '') {
            return null;
        }

        $thresholds = self::thresholds($scale);
        $mark = (float) $mark;

        return match (true) {
            $mark >= $thresholds['above'] => self::ABOVE,
            $mark >= $thresholds['in_line'] => self::IN_LINE,
            default => self::BELOW,
        };
    }

    /**
     * What the student is expected to reach in the next round: one level above
     * the judgment they earned this round, and Above once they are already
     * there. Confirmed by the client.
     */
    public static function nextExpectation(?string $judgement): ?string
    {
        return match ($judgement) {
            self::BELOW => self::IN_LINE,
            self::IN_LINE, self::ABOVE => self::ABOVE,
            default => null,
        };
    }

    /** "Round One 2026 - 2027", matching how rounds are named in Settings. */
    public static function roundLabel(int $round, int $academicYear): string
    {
        return (self::ROUNDS[$round] ?? "Round {$round}")." {$academicYear} - ".($academicYear + 1);
    }

    /** Marks are shown out of 20 per skill, or as a percentage of it. */
    public static function displayMark(int|float|null $mark, string $scale): string
    {
        if ($mark === null) {
            return '';
        }

        if (self::displayAsPercentage()) {
            return round($mark / self::MAX[$scale] * 100).'%';
        }

        // Trim a trailing .00 so a whole mark reads as "14", not "14.00".
        return rtrim(rtrim(number_format((float) $mark, 2, '.', ''), '0'), '.');
    }

    public static function displayAsPercentage(): bool
    {
        return self::settings()['mark_display'] === self::DISPLAY_PERCENTAGE;
    }

    /** Validation rules for the Settings form. */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::MAX as $scale => $max) {
            $inLine = "inputs.mark_{$scale}_in_line_from";

            $rules[$inLine] = ['required', 'integer', 'min:1', 'max:'.($max - 1)];
            $rules["inputs.mark_{$scale}_above_from"] = ['required', 'integer', "gt:{$inLine}", 'max:'.$max];
        }

        $rules['inputs.mark_display'] = ['required', 'in:'.self::DISPLAY_NUMBER.','.self::DISPLAY_PERCENTAGE];

        return $rules;
    }

    /** Readable field names for validation messages. */
    public static function attributes(): array
    {
        $attributes = ['inputs.mark_display' => 'show marks as'];

        foreach (self::LABELS as $scale => $label) {
            $attributes["inputs.mark_{$scale}_in_line_from"] = strtolower($label).' "In line starts at"';
            $attributes["inputs.mark_{$scale}_above_from"] = strtolower($label).' "Above starts at"';
        }

        return $attributes;
    }

    /** Forget the cached settings, e.g. straight after they are saved. */
    public static function flush(): void
    {
        self::$settings = null;
    }

    private static function settings(): array
    {
        return self::$settings ??= array_merge(
            self::DEFAULTS,
            Option::query()
                ->whereIn('key', array_keys(self::DEFAULTS))
                ->pluck('value', 'key')
                ->all()
        );
    }

    private static function isWholeNumber(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit(trim($value)));
    }
}
