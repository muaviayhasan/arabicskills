<?php

namespace App\Exports;

use App\Models\StudentExam;
use App\Support\ExamLevelHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttemptedExamsExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    /**
     * @var array{school_id:mixed,grade_id:mixed,level_id:mixed,term:mixed,status:mixed,year:mixed,archiveStatus:mixed,searchWord:mixed}
     */
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $schoolId = $this->filters['school_id'] ?? null;
        $gradeId = $this->filters['grade_id'] ?? null;
        $levelId = $this->filters['level_id'] ?? null;
        $term = $this->filters['term'] ?? null;
        $status = $this->filters['status'] ?? null;
        $year = $this->filters['year'] ?? null;
        $archiveStatus = $this->filters['archiveStatus'] ?? null;
        $searchWord = $this->filters['searchWord'] ?? null;

        return StudentExam::query()
            ->whereHas('Student', function (Builder $query) use ($archiveStatus, $year) {
                $query->applyArchiveFilters($archiveStatus, $year);
            })
            ->whereHas('Exam', function (Builder $query) use ($archiveStatus) {
                $query->applyArchiveFilters($archiveStatus);
            })

            ->when($schoolId, function (Builder $query) use ($schoolId) {
                $query->whereRelation('Exam', 'school_id', $schoolId);
            })

            ->when($gradeId, function (Builder $query) use ($gradeId) {
                $query->whereRelation('Exam', 'grade_id', $gradeId);
            })

            ->when($levelId, function (Builder $query) use ($levelId) {
                $query->whereHas('Exam', function (Builder $q) use ($levelId) {
                    $q->whereJsonContains('level_ids', (int) $levelId);
                });
            })

            ->when($term, function (Builder $query) use ($term) {
                $query->whereRelation('Exam', 'term', $term);
            })

            ->when($status, function (Builder $query) use ($status) {
                $query->whereAnySkillStatus($status);
            })

            ->when($searchWord, function (Builder $query) use ($searchWord, $archiveStatus, $year) {
                $query->whereHas('Student', function (Builder $q) use ($searchWord, $archiveStatus, $year) {
                    $q->applyArchiveFilters($archiveStatus, $year)
                        ->where(function (Builder $sub) use ($searchWord) {
                            $sub->where('name', 'like', '%'.$searchWord.'%')
                                ->orWhere('registration', 'like', '%'.$searchWord.'%')
                                ->orWhere('user_name', 'like', '%'.$searchWord.'%');
                        });
                });
            })

            ->where('checked', false)

            ->with([
                'Exam' => function ($q) use ($archiveStatus) {
                    $q->applyArchiveFilters($archiveStatus)
                        ->select('id', 'term', 'school_id', 'grade_id', 'level_ids', 'status', 'deleted_at')
                        ->with([
                            'School:id,name',
                            'Grade:id,name',
                        ]);
                },
                'Student' => function ($q) use ($archiveStatus, $year) {
                    $q->applyArchiveFilters($archiveStatus, $year)
                        ->select('id', 'name', 'registration', 'user_name', 'section_id', 'deleted_at');
                },
                'Student.Section:id,name',
            ])
            ->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Registration',
            'School',
            'Grade',
            'Student Section',
            'Level',
            'Exam Term',
            'Reading',
            'Listening',
            'Writing',
            'Speaking',
            'Sentences Structures',
        ];
    }

    public function map($row): array
    {
        return [
            optional($row->Student)->name,
            optional($row->Student)->registration,
            $row->Exam?->School?->name ?? '',
            $row->Exam?->Grade?->name ?? '',
            $row->Student?->Section?->name ?? '',
            ExamLevelHelper::levelNamesLabel($row->Exam?->level_ids ?? []),
            optional($row->Exam)->term,
            $this->statusOnly($row->reading_status ?? null),
            $this->statusOnly($row->listening_status ?? null),
            $this->statusOnly($row->writing_status ?? null),
            $this->statusOnly($row->speaking_status ?? null),
            $this->statusOnly($row->sentences_structures_status ?? null),
        ];
    }

    protected function statusOnly($value): ?string
    {

        $fallback = 'Absent';

        if ($value === null || $value === '' || $value === []) {
            return $fallback;
        }

        if (is_array($value)) {
            $statusRaw = $value['status'] ?? null;
            if ($statusRaw === null || $statusRaw === '') {
                return $fallback;
            }

            return Str::headline((string) $statusRaw);
        }

        if (is_string($value) && $value !== '') {
            if (preg_match('/s:6:\\"status\\";s:\\d+:\\"([^\\"]+)\\";/', $value, $m)) {
                return Str::headline($m[1]) ?? $fallback;
            }
            if (preg_match('/"status"\s*:\s*"([^"]+)"/', $value, $m)) {
                return Str::headline($m[1]) ?? $fallback;
            }
        }

        return $fallback;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('A1:L1')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $sheet->getStyle('A1:L1')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color' => ['rgb' => 'c2e3c7'],
        ]);
    }
}
