<?php

namespace App\Exports;

use App\Support\StudentDemographics;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Registration',
            'UserName',
            'Password',
            'School Name',
            'Grade/year',
            'Division',
            'Level',
            'Nationality',
            'Category',
            // Same order and headings as the upload template, so an exported
            // sheet can be edited and uploaded straight back.
            'Gender',
            'SEN',
            'G&T',
            'Citizen',
        ];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->registration,
            $row->user_name,
            $row->password,
            optional($row->School)->name,
            optional($row->Grade)->name,
            optional($row->Section)->name,
            optional($row->assignedLevel)->name ?? '',
            $row->nationality,
            $row->category,
            StudentDemographics::genderLabel($row->gender),
            StudentDemographics::flagLabel($row->sen),
            StudentDemographics::flagLabel($row->gifted_talented),
            StudentDemographics::flagLabel($row->citizen),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // ✅ Get total rows from query
        $count = (clone $this->query)->count() + 1; // +1 for heading row

        // Derived from the headings, so adding a column can't leave it unstyled.
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));

        // Header styling
        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color' => ['rgb' => 'c2e3c7'],
        ]);

        // Optional: apply borders to all rows
        $sheet->getStyle("A1:{$lastColumn}{$count}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }
}
