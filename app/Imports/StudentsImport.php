<?php

// app/Imports/StudentsImport.php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToArray, WithHeadingRow
{
    /**
     * Columns the import cannot work without, as they are written in the
     * template, against the keys a heading slugs down to. A heading matches if
     * any one of its keys is present, so older templates that say "Section"
     * rather than "Division" still load.
     *
     * @var array<string, list<string>>
     */
    public const REQUIRED_COLUMNS = [
        'Student Name' => ['student_name'],
        'Registration' => ['registration'],
        'School Name' => ['school_name'],
        'Grade/year' => ['gradeyear', 'grade_year', 'grade'],
        'Level' => ['level'],
    ];

    protected $students = [];

    /** @var list<string> headings as they came out of the file */
    protected array $headings = [];

    public function array(array $record)
    {
        foreach ($record as $i => $row) {
            if ($this->headings === [] && is_array($row)) {
                $this->headings = array_keys($row);
            }

            // Excel keeps rows that were formatted or merely touched, so a file
            // regularly carries empty rows past the last student. Read as
            // students they arrive with every field blank, which then reads as a
            // second, nameless school and fails the whole import.
            if ($this->isBlankRow($row)) {
                continue;
            }

            $this->students[$i]['school'] = trim(str_replace('  ', ' ', $row['school_name'] ?? ''));
            $this->students[$i]['name'] = trim(str_replace('  ', ' ', $row['student_name'] ?? ''));
            $this->students[$i]['registration'] = trim(str_replace('  ', ' ', $row['registration'] ?? ''));
            $this->students[$i]['user_name'] = trim(str_replace('  ', ' ', $row['username'] ?? $row['user_name'] ?? ''));
            $this->students[$i]['password'] = trim(str_replace('  ', ' ', $row['password'] ?? ''));
            $this->students[$i]['grade'] = trim(str_replace('  ', ' ', $row['gradeyear'] ?? ''));
            $this->students[$i]['section'] = trim(str_replace('  ', ' ', $row['division'] ?? $row['section'] ?? ''));
            $this->students[$i]['level'] = trim(str_replace('  ', ' ', $row['level'] ?? ''));
            $this->students[$i]['nationality'] = trim(str_replace('  ', ' ', $row['nationality'] ?? ''));
            $this->students[$i]['category'] = trim(str_replace('  ', ' ', $row['category'] ?? ''));

            // Optional columns. Older templates don't have them, which reads as
            // blank and leaves any saved value untouched.
            $this->students[$i]['gender'] = trim((string) ($row['gender'] ?? ''));
            $this->students[$i]['sen'] = trim((string) ($row['sen'] ?? ''));
            // "G&T" slugs to `gt`, "G & T" to `g_t`.
            $this->students[$i]['gifted_talented'] = trim((string) (
                $row['gt'] ?? $row['g_t'] ?? $row['gifted_talented'] ?? $row['gifted_and_talented'] ?? ''
            ));
            $this->students[$i]['citizen'] = trim((string) ($row['citizen'] ?? ''));
        }
    }

    /**
     * A row with no name, no registration and no username is not a student,
     * whatever else it may carry.
     *
     * @param  array<string, mixed>  $row
     */
    protected function isBlankRow(array $row): bool
    {
        $identifiers = [
            $row['student_name'] ?? '',
            $row['registration'] ?? '',
            $row['username'] ?? $row['user_name'] ?? '',
        ];

        foreach ($identifiers as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * The headings the file actually had, slugged the way the reader slugs them.
     *
     * @return list<string>
     */
    public function getHeadings(): array
    {
        return $this->headings;
    }

    /**
     * Required columns the file does not have, by their template names. A file
     * missing one of these used to import anyway, reading the column as blank,
     * which failed later as a confusing row error rather than saying the column
     * was absent.
     *
     * @return list<string>
     */
    public function missingColumns(): array
    {
        $missing = [];

        foreach (self::REQUIRED_COLUMNS as $label => $keys) {
            if (array_intersect($keys, $this->headings) === []) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function getStudents()
    {
        // Skipped rows leave gaps in the keys; the caller wants a plain list.
        return array_values($this->students);
    }
}
