<?php

// app/Imports/StudentsImport.php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToArray, WithHeadingRow
{
    protected $students = [];

    public function array(array $record)
    {
        foreach ($record as $i => $row) {
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

    public function getStudents()
    {
        // Skipped rows leave gaps in the keys; the caller wants a plain list.
        return array_values($this->students);
    }
}
