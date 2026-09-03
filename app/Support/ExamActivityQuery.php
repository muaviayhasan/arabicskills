<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Exam;
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
