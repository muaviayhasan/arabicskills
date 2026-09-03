<?php

namespace App\Support;

class LevelLabelParser
{
    private const WORD_NUMBERS = [
        'one' => 1,
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
        'ten' => 10,
        'i' => 1,
        'ii' => 2,
        'iii' => 3,
        'iv' => 4,
        'v' => 5,
        'vi' => 6,
        'vii' => 7,
        'viii' => 8,
        'ix' => 9,
        'x' => 10,
    ];

    /**
     * Normalize a legacy level label for consistent map lookups only.
     * Original strings are stored as-is in legacy_labels JSON.
     */
    public static function normalize(?string $label): string
    {
        $label = (string) ($label ?? '');
        $label = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $label);
        $label = str_replace(['–', '—'], '-', $label);
        $label = preg_replace('/\s+/u', ' ', trim($label)) ?? trim($label);
        $label = preg_replace('/\s*-\s*/u', ' - ', $label) ?? $label;
        $label = preg_replace('/\s*\\\s*/u', ' \\ ', $label) ?? $label;

        return mb_strtolower($label);
    }

    /**
     * Extract the LEVEL number from legacy strings with flexible spacing and word/number tokens.
     */
    public static function extractLevelNumber(?string $label): ?int
    {
        $label = trim((string) ($label ?? ''));

        if ($label === '') {
            return null;
        }

        if (preg_match('/LEVEL\s*\(\s*([^)]+?)\s*\)/iu', $label, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/LEVEL\s+([A-Z]+|\d+)/iu', $label, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/(?:\\\\|\|)\s*LEVEL\s+(\d+|[A-Z]+)/iu', $label, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Extract level number from canonical import values (plain number or "Level N").
     */
    public static function extractCanonicalLevelNumber(?string $value): ?int
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^level\s+(\d+|[a-z]+)$/iu', $value, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null && $parsed >= 1 && $parsed <= 10) {
                return $parsed;
            }
        }

        if (preg_match('/^\d+$/', $value)) {
            $number = (int) $value;

            if ($number >= 1 && $number <= 10) {
                return $number;
            }
        }

        return null;
    }

    /**
     * Extract the YEAR number from legacy level strings.
     */
    public static function extractYearNumber(?string $label): ?int
    {
        $label = trim((string) ($label ?? ''));

        if ($label === '') {
            return null;
        }

        if (preg_match('/YEAR\s*\(\s*([^)]+?)\s*\)/iu', $label, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/YEAR\s+(\d+|[A-Z]+)/iu', $label, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/(?:\\\\|\|)\s*YEAR\s+(\d+|[A-Z]+)/iu', $label, $matches)) {
            $parsed = self::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        return null;
    }

    /**
     * Parse a level token from inside LEVEL (...) or after LEVEL keyword.
     */
    public static function parseLevelToken(?string $token): ?int
    {
        $token = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) ($token ?? '')) ?? ''));

        if ($token === '') {
            return null;
        }

        if (isset(self::WORD_NUMBERS[$token])) {
            return self::WORD_NUMBERS[$token];
        }

        if (preg_match('/^\d+$/', $token)) {
            return (int) $token;
        }

        return null;
    }
}
