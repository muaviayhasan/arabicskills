<?php

namespace App\Support;

use App\Models\Student;

/**
 * Turns one row of the marks sheet into a student, their marks per round, and
 * anything wrong with it.
 *
 * The rules, all confirmed by the client:
 *  - Students are matched on Student ID; leading zeros are ignored, so 0011694
 *    and 11694 are the same student.
 *  - Marks are only added to students who already exist. Nobody is created.
 *  - A round with no marks at all was not assessed, and is skipped.
 *  - A blank skill in a round that does have marks means absent, and counts 0.
 *  - A mark above the maximum, or that is not a number, rejects the row.
 */
class AssessmentRowResolver
{
    public function __construct(private readonly int $academicYear) {}

    /**
     * @return array{
     *     registration: string,
     *     student: Student|null,
     *     rounds: array<int, array{marks: array<string, float>, total: float}>,
     *     errors: list<string>
     * }
     */
    public function resolve(array $row): array
    {
        $registration = trim((string) ($row[AssessmentSheet::STUDENT_ID_KEY] ?? ''));

        $result = [
            'registration' => $registration,
            'student' => null,
            'rounds' => [],
            'errors' => [],
        ];

        if ($registration === '') {
            $result['errors'][] = 'Student ID is missing.';

            return $result;
        }

        $result['student'] = $this->findStudent($registration);

        if (! $result['student']) {
            $result['errors'][] = "No student with ID {$registration} in {$this->academicYear}.";
        }

        foreach (array_keys(MarkRanges::ROUNDS) as $round) {
            [$marks, $errors] = $this->resolveRound($row, $round);

            $result['errors'] = array_merge($result['errors'], $errors);

            // An untouched round simply was not assessed.
            if ($marks !== []) {
                $result['rounds'][$round] = [
                    'marks' => $marks,
                    'total' => array_sum($marks),
                ];
            }
        }

        if ($result['rounds'] === [] && $result['errors'] === []) {
            $result['errors'][] = 'No marks filled in for any round.';
        }

        return $result;
    }

    /**
     * @return array{0: array<string, float>, 1: list<string>}
     */
    private function resolveRound(array $row, int $round): array
    {
        $max = MarkRanges::MAX[MarkRanges::SKILL];
        $values = [];
        $errors = [];
        $anyFilled = false;

        foreach (MarkRanges::SKILLS as $skill => $skillLabel) {
            $raw = trim((string) ($row[AssessmentSheet::markKey($round, $skill)] ?? ''));
            $where = MarkRanges::ROUNDS[$round].' '.$skillLabel;

            if ($raw === '') {
                $values[$skill] = null;

                continue;
            }

            $anyFilled = true;

            if (! is_numeric($raw)) {
                $errors[] = "{$where}: '{$raw}' is not a number.";

                continue;
            }

            $mark = (float) $raw;

            if ($mark < 0 || $mark > $max) {
                $errors[] = "{$where}: {$raw} must be between 0 and {$max}.";

                continue;
            }

            $values[$skill] = $mark;
        }

        if (! $anyFilled) {
            return [[], $errors];
        }

        // The round was assessed, so a blank skill means absent, which is 0.
        return [array_map(fn ($mark) => $mark ?? 0.0, $values), $errors];
    }

    private function findStudent(string $registration): ?Student
    {
        $trimmed = ltrim($registration, '0');

        return Student::query()
            ->with(['School', 'Grade', 'Section', 'assignedLevel'])
            ->where('year', $this->academicYear)
            ->where(function ($query) use ($registration, $trimmed) {
                $query->whereIn('registration', array_unique([$registration, $trimmed]))
                    // Covers IDs stored with leading zeros too.
                    ->orWhereRaw("TRIM(LEADING '0' FROM registration) = ?", [$trimmed]);
            })
            ->first();
    }
}
