<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads the marks sheet as rows keyed by heading.
 *
 * Safe here only because every mark column has a unique heading
 * (see AssessmentSheet); repeated headings would overwrite each other.
 */
class AssessmentMarksImport implements ToArray, WithHeadingRow
{
    private array $rows = [];

    public function array(array $rows): void
    {
        foreach ($rows as $row) {
            // Skip rows left completely empty at the bottom of a sheet.
            if (implode('', array_map(fn ($value) => trim((string) $value), $row)) === '') {
                continue;
            }

            $this->rows[] = $row;
        }
    }

    public function rows(): array
    {
        return $this->rows;
    }
}
