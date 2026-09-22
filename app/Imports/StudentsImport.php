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
            $this->students[$i]['school'] = trim(str_replace('  ', ' ', $row['school_name'] ?? ""));
            $this->students[$i]['name'] = trim(str_replace('  ', ' ', $row['student_name'] ?? ""));
            $this->students[$i]['registration'] = trim(str_replace('  ', ' ', $row['registration'] ?? ""));
            $this->students[$i]['user_name'] = trim(str_replace('  ', ' ', $row['username'] ?? $row['user_name'] ?? ""));
            $this->students[$i]['password'] = trim(str_replace('  ', ' ', $row['password'] ?? ""));
            $this->students[$i]['grade'] = trim(str_replace('  ', ' ', $row['gradeyear'] ?? ""));
            $this->students[$i]['section'] = trim(str_replace('  ', ' ', $row['division'] ?? $row['section'] ?? ""));
            $this->students[$i]['level'] = trim(str_replace('  ', ' ', $row['level'] ?? ""));
            $this->students[$i]['nationality'] = trim(str_replace('  ', ' ', $row['nationality'] ?? ""));
            $this->students[$i]['category'] = trim(str_replace('  ', ' ', $row['category'] ?? ""));

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

    public function getStudents()
    {
        return $this->students;
    }
}
