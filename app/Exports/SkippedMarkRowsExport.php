<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * The rows an import could not accept, with the reason, so the admin can fix
 * them and upload just those again.
 */
class SkippedMarkRowsExport implements FromArray, WithHeadings, WithStyles
{
    public function __construct(private readonly array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Row', 'Student ID', 'Student Name', 'Reason'];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows) + 1;

        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle("A1:D{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        foreach (range('A', 'D') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }
}
