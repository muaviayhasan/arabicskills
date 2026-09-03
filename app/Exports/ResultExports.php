<?php

namespace App\Exports;

use App\Models\Level;
use App\Models\StudentExam;
use App\Support\ExamLevelHelper;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ResultExports implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $searchColumn;

    protected $searchWord;

    protected $schoolId;

    protected $year;

    protected $archiveStatus;

    public function __construct($searchColumn, $searchWord, $schoolId = null, $year = null, $archiveStatus = 'active')
    {
        $this->searchColumn = $searchColumn;
        $this->searchWord = $searchWord;
        $this->schoolId = $schoolId;
        $this->year = $year;
        $this->archiveStatus = $archiveStatus;
    }

    public function collection()
    {
        return StudentExam::query()
            ->whereHas('Student', function ($query) {
                $query->applyArchiveFilters($this->archiveStatus, $this->year);
            })
            ->whereHas('Exam', function ($query) {
                $query->applyArchiveFilters($this->archiveStatus);
            })
            ->when($this->searchColumn && $this->searchColumn == 'term', function ($query) {
                $query->whereRelation('Exam', 'term', 'LIKE', "%{$this->searchWord}%");
            })
            ->when($this->searchColumn && $this->searchColumn == 'grade', function ($query) {
                $query->whereRelation('Exam.Grade', 'name', 'LIKE', "%{$this->searchWord}%");
            })
            ->when($this->searchColumn && $this->searchColumn == 'section', function ($query) {
                $query->whereHas('Student.Section', function ($q) {
                    $q->where('name', 'LIKE', "%{$this->searchWord}%");
                });
            })
            ->when($this->searchColumn && $this->searchColumn == 'level', function ($query) {
                $matchingLevelIds = Level::query()
                    ->where('name', 'LIKE', "%{$this->searchWord}%")
                    ->pluck('id');

                if ($matchingLevelIds->isEmpty()) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereHas('Exam', function ($q) use ($matchingLevelIds) {
                    $q->where(function ($sub) use ($matchingLevelIds) {
                        foreach ($matchingLevelIds as $levelId) {
                            $sub->orWhereJsonContains('level_ids', (int) $levelId);
                        }
                    });
                });
            })
            ->when($this->searchColumn && $this->searchColumn == 'student', function ($query) {
                $query->whereRelation('Student', 'registration', 'LIKE', "%{$this->searchWord}%");
            })
            ->when($this->schoolId, function ($query) {
                $query->whereRelation('Exam', 'school_id', $this->schoolId);
            })
            ->where('checked', true)
            ->with(['Exam' => function ($quer) {
                $quer->applyArchiveFilters($this->archiveStatus)
                    ->select('id', 'term', 'school_id', 'grade_id', 'level_ids', 'deleted_at')
                    ->with('Grade:id,name')
                    ->with('School:id,name');
            }])
            ->with(['Student' => function ($quer) {
                $quer->applyArchiveFilters($this->archiveStatus, $this->year)
                    ->select('id', 'name', 'registration', 'section_id', 'deleted_at');
            }])
            ->with('Student.Section:id,name')
            ->with('Result')
            ->orderByDESC('created_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Registration',
            'Exam Term',
            'School',
            'Grade',
            'Student Section',
            'Level',
            'Reading Marks',
            'Listening Marks',
            'Writing Marks',
            'Speaking Marks',
            'Total',
        ];
    }

    public function map($row): array
    {

        $reading_marks = collect($row->Result->reading_marks ?? [])->sum();
        $listening_marks = collect($row->Result->listening_marks ?? [])->sum();
        $writing_marks = collect($row->Result->writing_marks ?? [])->sum();
        $speaking_marks = collect($row->Result->speaking_marks ?? [])->sum();

        return [
            optional($row->Student)->name,
            optional($row->Student)->registration,
            optional($row->Exam)->term,
            $row->Exam->School->name ?? '',
            $row->Exam->Grade->name ?? '',
            $row->Student?->Section?->name ?? '',
            ExamLevelHelper::levelNamesLabel($row->Exam?->level_ids ?? []),
            $reading_marks,
            $listening_marks,
            $writing_marks,
            $speaking_marks,
            $reading_marks + $listening_marks + $writing_marks + $speaking_marks,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000'],
                ],
            ],
        ]);

        $sheet->getStyle('A1:K1')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => 'c2e3c7'],
        ]);
    }
}
