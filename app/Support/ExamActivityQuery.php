<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExamActivityQuery
{
    public static function baseQuery(Exam $exam, ?string $type = null): Builder
    {
        $query = Activity::query()
            ->where('term', $exam->term)
            ->where('grade_id', $exam->grade_id);

        $levelIds = $exam->normalizedLevelIds();

        if ($levelIds !== []) {
            $query->whereIn('level_id', $levelIds);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query;
    }

    /**
     * The activity ids one student should actually be served for a skill.
     *
     * An exam can be created for several levels at once, and the activity list
     * it stores is flat, so it holds every level's activities together. A
     * student sits only the activities for their own level.
     *
     * Activities with no level recorded are kept. They pre-date level tagging
     * and are still attached to older exams, so dropping them would leave
     * those papers empty.
     *
     * @return list<int>
     */
    public static function activityIdsForStudent(Exam $exam, ?Student $student, string $type): array
    {
        return self::activityIdsForLevel($exam, $student?->level_id, $type);
    }

    /**
     * The same rule, for one level rather than one student. Used by the admin
     * preview, so that what an admin previews for a level is exactly what a
     * student on that level will sit.
     *
     * @return list<int>
     */
    public static function activityIdsForLevel(Exam $exam, int|string|null $levelId, string $type): array
    {
        $assigned = $exam->{$type.'_activities'} ?? [];

        if (! is_array($assigned) || $assigned === []) {
            return [];
        }

        // A single-level exam cannot mix levels, so there is nothing to filter.
        if (count($exam->normalizedLevelIds()) < 2) {
            return array_values($assigned);
        }

        $levelId = $levelId ? (int) $levelId : null;

        // Without a level there is no paper to choose. A student in this state
        // cannot reach an exam at all, as Exam::applyStudentLevelScope matches
        // no exam for them, so this only guards the admin screens — and there,
        // showing the whole paper is less harmful than showing nothing.
        if ($levelId === null) {
            return array_values($assigned);
        }

        $levels = Activity::whereIn('id', $assigned)->pluck('level_id', 'id');

        $filtered = array_filter($assigned, function ($id) use ($levels, $levelId) {
            $activityLevelId = $levels->get((int) $id);

            return $activityLevelId === null || (int) $activityLevelId === $levelId;
        });

        // The stored order is the order the paper is sat in; keep it.
        return array_values($filtered);
    }

    /**
     * Per-level coverage of a multi-level exam: how many activities each level
     * actually gets per skill, and which skills it has none of.
     *
     * The count an admin sees on the exam card is the total across every level,
     * which says nothing about whether any one level has a paper to sit. This
     * applies the same rule the students get, so the two can never disagree.
     *
     * Empty for a single-level exam, which cannot have gaps of this kind.
     *
     * @param  list<string>  $types
     * @return list<array{level: string, counts: array<string, int>, missing: list<string>}>
     */
    public static function levelCoverage(Exam $exam, array $types): array
    {
        $levelIds = $exam->normalizedLevelIds();

        if (count($levelIds) < 2) {
            return [];
        }

        $assigned = [];

        foreach ($types as $type) {
            $ids = $exam->{$type.'_activities'} ?? [];
            $assigned[$type] = is_array($ids) ? $ids : [];
        }

        $allIds = array_values(array_unique(array_merge(...array_values($assigned)) ?: []));

        if ($allIds === []) {
            return [];
        }

        $activityLevels = Activity::whereIn('id', $allIds)->pluck('level_id', 'id');
        $levels = Level::whereIn('id', $levelIds)->orderBy('number')->get();

        $coverage = [];

        foreach ($levels as $level) {
            $counts = [];
            $missing = [];

            foreach ($types as $type) {
                $counts[$type] = count(array_filter($assigned[$type], function ($id) use ($activityLevels, $level) {
                    $activityLevelId = $activityLevels->get((int) $id);

                    // Untagged activities count for every level, as they are served to every level.
                    return $activityLevelId === null || (int) $activityLevelId === (int) $level->id;
                }));

                // Only a real gap if the exam carries that skill for somebody. A skill
                // the exam leaves out entirely is a choice, not a level being short-changed.
                if ($counts[$type] === 0 && $assigned[$type] !== []) {
                    $missing[] = $type;
                }
            }

            $coverage[] = ['level' => $level->name, 'counts' => $counts, 'missing' => $missing];
        }

        return $coverage;
    }

    public static function hasForPlacement(int $gradeId, array $levelIds, string $term, ?string $type = null): bool
    {
        $query = Activity::query()
            ->where('grade_id', $gradeId)
            ->where('term', $term);

        $levelIds = ExamLevelHelper::normalizeLevelIds($levelIds);

        if ($levelIds !== []) {
            $query->whereIn('level_id', $levelIds);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->exists();
    }

    public static function pickerCollection(Exam $exam, string $type): Collection
    {
        $assigned = $exam->{$type.'_activities'} ?? [];

        if ($assigned === []) {
            return self::baseQuery($exam, $type)
                ->orderByDesc('created_at')
                ->get();
        }

        $query = Activity::query()->where(function ($outer) use ($exam, $type, $assigned) {
            $outer->where(function ($sub) use ($exam, $type) {
                $sub->where('type', $type)
                    ->where('term', $exam->term)
                    ->where('grade_id', $exam->grade_id);

                $levelIds = $exam->normalizedLevelIds();

                if ($levelIds !== []) {
                    $sub->whereIn('level_id', $levelIds);
                }
            })->orWhereIn('id', $assigned);
        });

        $query->orderByRaw(
            'CASE WHEN id IN ('.implode(',', array_fill(0, count($assigned), '?')).') THEN 1 ELSE 2 END',
            $assigned
        );

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * @param  list<string>  $types
     */
    public static function assignAllTypesToExam(Exam $exam, array $types): void
    {
        $activities = self::baseQuery($exam)->get();

        foreach ($types as $type) {
            $exam->{$type.'_activities'} = $activities->where('type', $type)->pluck('id')->values()->all();
        }

        $exam->save();
    }
}
