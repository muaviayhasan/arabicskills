<?php

namespace App\Exports;

use App\Models\Level;
use App\Models\StudentExam;
use App\Support\ExamLevelHelper;
use App\Support\MarkRanges;
use App\Support\StudentDemographics;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exam results in the client's own mark sheet layout: one row per student,
 * with all three rounds side by side.
 *
 * Their sheet runs to column AU — eleven columns identifying the student, then
 * a block per round. This adds Sentence Structures, the fifth skill they asked
 * for, so each block carries two more columns and the sheet ends at BA.
 *
 * A skill's mark is the sum of the per-question marks the marker gave.
 * Judgments and expectations come from the ranges set in Settings.
 */
class ResultExports implements FromArray, WithHeadings, WithStyles
{
    /** Their column order, with their bilingual headings. */
    private const SKILLS = [
        'reading_marks' => 'Reading - القراءة',
        'listening_marks' => 'Listening - الاستماع',
        'writing_marks' => 'Writing - الكتابة',
        'speaking_marks' => 'Speaking - التحدث',
        'sentences_structures_marks' => 'Sentence Structures - بنية الجملة',
    ];

    private const INFO_HEADINGS = [
        'Student ID', 'Student Name', 'School', 'Section', 'Grade',
        'Grade Name', 'Gender', 'Nationality', 'SEN', 'G&T', 'Citizen',
    ];

    private const FILLS = [
        MarkRanges::BELOW => 'FF0000',
        MarkRanges::IN_LINE => 'FFFF00',
        MarkRanges::ABOVE => '92D050',
    ];

    public function __construct(
        protected $searchColumn,
        protected $searchWord,
        protected $schoolId = null,
        protected $year = null,
        protected $archiveStatus = 'active',
        protected $gradeId = null,
        protected $levelId = null,
    ) {}

    /**
     * Columns per round: level, five mark/judgment pairs, then total, its
     * judgment, the expectation and progress.
     */
    private function blockWidth(): int
    {
        return 1 + (count(self::SKILLS) * 2) + 4;
    }

    public function headings(): array
    {
        $headings = self::INFO_HEADINGS;

        foreach (MarkRanges::ROUNDS as $label) {
            $headings[] = "The Assessment - {$label}";

            foreach (self::SKILLS as $skill) {
                $headings[] = $skill;
                $headings[] = 'Judgment';
            }

            $headings[] = 'Total';
            // Their sheet jumps from Total to Expectations, which reads as if
            // Expectations judges the total. It does not: it is the target for
            // the next round. The total's own judgment belongs here, as in
            // their mark range document.
            $headings[] = 'Overall Judgment';
            $headings[] = 'Expectations';
            $headings[] = 'The Progress';
        }

        return $headings;
    }

    /**
     * One row per student, each round placed in its own block.
     */
    public function array(): array
    {
        $rows = [];

        foreach ($this->results()->groupBy('student_id') as $studentResults) {
            $student = $studentResults->first()->Student;

            if (! $student) {
                continue;
            }

            $row = $this->studentColumns($student);

            // Which round a result belongs to comes from its exam's term,
            // e.g. "Round Two 2026 - 2027".
            $byRound = $studentResults->keyBy(fn ($result) => $this->roundOf($result->Exam?->term));

            foreach (array_keys(MarkRanges::ROUNDS) as $round) {
                array_push($row, ...$this->roundColumns($byRound->get($round)));
            }

            $rows[] = $row;
        }

        // Sort by student name, as their sheet is.
        usort($rows, fn ($a, $b) => strcasecmp((string) $a[1], (string) $b[1]));

        return $rows;
    }

    /** The eleven columns identifying the student. */
    private function studentColumns($student): array
    {
        $grade = $student->Grade;
        $section = $student->Section;

        return [
            (string) $student->registration,
            $student->name,
            $student->School->name ?? '',
            // Their sheet holds "Non-Arabs" here, which is the student category.
            $student->category ?? '',
            $grade->number ?? '',
            // Their "Grade Name" reads like 9B: the year plus the section.
            trim(($grade->number ?? '').($section->name ?? '')),
            StudentDemographics::genderLabel($student->gender),
            $student->nationality ?? '',
            StudentDemographics::flagLabel($student->sen),
            StudentDemographics::flagLabel($student->gifted_talented),
            StudentDemographics::flagLabel($student->citizen),
        ];
    }

    /**
     * One round's block. A round the student did not sit comes back blank.
     */
    private function roundColumns($result): array
    {
        if (! $result) {
            return array_fill(0, $this->blockWidth(), '');
        }

        // The client uses this column for the level the student sat at.
        $block = [ExamLevelHelper::levelNamesLabel($result->Exam?->level_ids ?? [])];

        $total = 0;
        $anyMarked = false;

        foreach (array_keys(self::SKILLS) as $column) {
            $marks = $result->Result->{$column} ?? [];

            // Nothing recorded means the skill has not been marked yet. Left
            // blank rather than summed to 0, which would read as "Below".
            if (! is_array($marks) || $marks === []) {
                $block[] = '';
                $block[] = '';

                continue;
            }

            $mark = collect($marks)->sum();
            $total += $mark;
            $anyMarked = true;

            $block[] = $mark;
            $block[] = MarkRanges::judge($mark, MarkRanges::SKILL);
        }

        $overall = $anyMarked ? MarkRanges::judge($total, MarkRanges::TOTAL) : null;

        $block[] = $anyMarked ? $total : '';
        $block[] = $overall ?? '';
        $block[] = MarkRanges::nextExpectation($overall) ?? '';
        $block[] = ''; // The Progress — on hold until the client shares the rules.

        return $block;
    }

    /** "Round Two 2026 - 2027" => 2 */
    private function roundOf(?string $term): ?int
    {
        foreach (MarkRanges::ROUNDS as $number => $label) {
            if ($term && str_starts_with(trim($term), $label)) {
                return $number;
            }
        }

        return null;
    }

    private function results()
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
            // The Grade and Level dropdowns on the Results page.
            ->when($this->gradeId, function ($query) {
                $query->whereRelation('Exam', 'grade_id', $this->gradeId);
            })
            ->when($this->levelId, function ($query) {
                $query->whereHas('Exam', function ($exam) {
                    $exam->whereJsonContains('level_ids', (int) $this->levelId);
                });
            })
            ->where('checked', true)
            ->with([
                'Exam' => fn ($query) => $query->applyArchiveFilters($this->archiveStatus)
                    ->select('id', 'term', 'school_id', 'grade_id', 'level_ids', 'deleted_at'),
                'Student' => fn ($query) => $query->applyArchiveFilters($this->archiveStatus, $this->year),
                'Student.School:id,name',
                'Student.Grade:id,name,number',
                'Student.Section:id,name',
                'Result',
            ])
            ->get();
    }

    public function styles(Worksheet $sheet)
    {
        $columnCount = count($this->headings());
        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$lastColumn}1")->getFill()->applyFromArray([
            'fillType' => 'solid',
            'color' => ['rgb' => 'c2e3c7'],
        ]);
        $sheet->getStyle("A1:{$lastColumn}1")->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        for ($i = 1; $i <= $columnCount; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        // The sheet is wide; keep the student's name in view while scrolling.
        $sheet->freezePane('C2');

        $this->colourJudgements($sheet, $lastRow);

        return [];
    }

    /** Judgment and expectation cells, filled the way their sheet is. */
    private function colourJudgements(Worksheet $sheet, int $lastRow): void
    {
        $columns = [];

        foreach (array_keys(MarkRanges::ROUNDS) as $index => $round) {
            $blockStart = count(self::INFO_HEADINGS) + ($index * $this->blockWidth()) + 1;

            // A judgment sits after each skill's mark.
            for ($skill = 0; $skill < count(self::SKILLS); $skill++) {
                $columns[] = $blockStart + 2 + ($skill * 2);
            }

            $afterSkills = $blockStart + 1 + (count(self::SKILLS) * 2);
            $columns[] = $afterSkills + 1; // Overall Judgment
            $columns[] = $afterSkills + 2; // Expectations
        }

        foreach ($columns as $index) {
            $letter = Coordinate::stringFromColumnIndex($index);

            for ($row = 2; $row <= $lastRow; $row++) {
                $value = $sheet->getCell("{$letter}{$row}")->getValue();

                if (! isset(self::FILLS[$value])) {
                    continue;
                }

                $sheet->getStyle("{$letter}{$row}")->applyFromArray([
                    'fill' => ['fillType' => 'solid', 'color' => ['rgb' => self::FILLS[$value]]],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $value === MarkRanges::BELOW ? 'FFFFFF' : '000000'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
            }
        }
    }
}
