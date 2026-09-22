<?php

namespace App\Exports;

use App\Support\AssessmentSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The blank marks sheet an admin downloads, with their students already filled
 * in so only marks need typing.
 */
class AssessmentTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return AssessmentSheet::headings();
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
        $lastRow = count($this->rows) + 1;

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color' => ['rgb' => 'c2e3c7'],
        ]);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Student ID must stay text, or Excel eats leading zeros.
        $sheet->getStyle("A2:A{$lastRow}")->getNumberFormat()->setFormatCode('@');

        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }
}
