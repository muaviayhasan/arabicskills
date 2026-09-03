<?php

namespace App\Support;

class GradeLabelParser
{
    /**
     * Normalize a grade name for lookup keys only.
     */
    public static function normalize(?string $name): string
    {
        $name = (string) ($name ?? '');
        $name = str_replace(["\xC2\xA0", "\u{00A0}"], ' ', $name);
        $name = preg_replace('/\s+/u', ' ', trim($name)) ?? trim($name);

        return mb_strtolower($name);
    }

    /**
     * Extract year number from a grade name (Year 1, YEAR ONE, plain 1-12, etc.).
     */
    public static function extractYearNumber(?string $name): ?int
    {
        $name = trim((string) ($name ?? ''));

        if ($name === '') {
            return null;
        }

        if (preg_match('/^year\s+([a-z]+|\d+)$/iu', $name, $matches)) {
            $parsed = LevelLabelParser::parseLevelToken($matches[1]);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        if (preg_match('/^\d+$/', $name)) {
            $number = (int) $name;

            if ($number >= 1 && $number <= 12) {
                return $number;
            }
        }

        return null;
    }
}
