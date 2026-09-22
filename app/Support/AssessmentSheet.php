<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Str;

/**
 * The layout of the marks upload sheet.
 *
 * Every mark column has a unique heading ("Round One - Reading"), unlike the
 * client's original sheet where twelve columns are all called "Judgment".
 * Repeated headings collapse into one when a sheet is read by heading, which
 * silently loses two of the three rounds, so the template avoids them.
 *
 * Judgments, totals and expectations are not columns: the system works them out
 * from the marks and the admin's ranges.
 */
class AssessmentSheet
{
    /** Columns identifying the student. Only Student ID is read on import. */
    public const INFO_HEADINGS = [
        'Student ID',
        'Student Name',
        'School',
        'Grade',
        'Section',
        'Level',
    ];

    public const STUDENT_ID_KEY = 'student_id';

    /** "Round One - Reading" */
    public static function markHeading(int $round, string $skill): string
    {
        return MarkRanges::ROUNDS[$round].' - '.MarkRanges::SKILLS[$skill];
    }

    /** The key that heading becomes once the sheet is read. */
    public static function markKey(int $round, string $skill): string
    {
        return Str::slug(self::markHeading($round, $skill), '_');
    }

    /**
     * Every heading, in order: student columns, then five skills per round.
     *
     * @return list<string>
     */
    public static function headings(): array
    {
        $headings = self::INFO_HEADINGS;

        foreach (array_keys(MarkRanges::ROUNDS) as $round) {
            foreach (array_keys(MarkRanges::SKILLS) as $skill) {
                $headings[] = self::markHeading($round, $skill);
            }
        }

        return $headings;
    }

    /**
     * One template row per student, with their details filled in and the mark
     * columns left blank, so the admin only types marks.
     *
     * @return list<list<string>>
     */
    public static function rowsFor(iterable $students): array
    {
        $rows = [];

        foreach ($students as $student) {
            $row = [
                (string) $student->registration,
                (string) $student->name,
                (string) ($student->School->name ?? ''),
                (string) ($student->Grade->name ?? ''),
                (string) ($student->Section->name ?? ''),
                (string) ($student->assignedLevel->name ?? ''),
            ];

            // Blank mark columns for every round.
            $rows[] = array_pad($row, count(self::headings()), '');
        }

        return $rows;
    }

    /** Students of a school, in the order they appear on the Students page. */
    public static function studentsFor(int $schoolId, int $academicYear)
    {
        return Student::query()
            ->with(['School', 'Grade', 'Section', 'assignedLevel'])
            ->where('school_id', $schoolId)
            ->where('year', $academicYear)
            ->orderBy('name')
            ->get();
    }
}
