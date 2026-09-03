<?php

namespace App\Support;

use App\Models\Grade;
use Illuminate\Support\Facades\DB;

class GradeLegacyResolver
{
    public function __construct(
        private int $adminId,
        private array $oldGradeIdMap = [],
        private array $canonicalGradeIds = [],
    ) {}

    public static function make(): self
    {
        $adminId = (int) (DB::table('admins')->orderBy('id')->value('id') ?? 1);
        $resolver = new self($adminId);
        $resolver->loadMaps();

        return $resolver;
    }

    public function loadMaps(): void
    {
        $this->oldGradeIdMap = [];
        $this->canonicalGradeIds = [];

        Grade::query()
            ->whereNotNull('number')
            ->get(['id', 'number', 'old_grades'])
            ->each(function (Grade $grade) {
                $this->canonicalGradeIds[$grade->id] = $grade->number;

                foreach ($grade->old_grades ?? [] as $oldGradeId) {
                    $this->oldGradeIdMap[(int) $oldGradeId] = $grade->id;
                }
            });
    }

    public function resolveGradeId(?int $currentGradeId, ?string $levelString): ?int
    {
        if ($currentGradeId !== null) {
            if (isset($this->oldGradeIdMap[$currentGradeId])) {
                return $this->oldGradeIdMap[$currentGradeId];
            }

            if (isset($this->canonicalGradeIds[$currentGradeId])) {
                return $currentGradeId;
            }
        }

        $levelString = trim((string) ($levelString ?? ''));

        if ($levelString === '') {
            return null;
        }

        $yearNumber = LevelLabelParser::extractYearNumber($levelString);

        if ($yearNumber === null) {
            return null;
        }

        $grade = Grade::firstOrCreate(
            ['number' => $yearNumber],
            [
                'name' => Grade::nameFromNumber($yearNumber),
                'admin_id' => $this->adminId,
                'old_grades' => [],
            ]
        );

        $this->canonicalGradeIds[$grade->id] = $grade->number;

        return $grade->id;
    }

    /**
     * Resolve grade id from legacy name/id, level string YEAR token, or canonical grade column.
     *
     * @param  array<string, int>  $gradeNameToId  normalized grade name => grade id
     */
    public function resolveFromImportValue(?string $gradeName, ?string $levelString, array $gradeNameToId): ?int
    {
        $candidateId = null;
        $normalizedGrade = GradeLabelParser::normalize($gradeName);

        if ($normalizedGrade !== '') {
            $candidateId = $gradeNameToId[$normalizedGrade] ?? null;
        }

        $resolved = $this->resolveGradeId($candidateId, $levelString);

        if ($resolved !== null) {
            return $resolved;
        }

        $yearNumber = GradeLabelParser::extractYearNumber($gradeName);

        if ($yearNumber === null) {
            return null;
        }

        return $this->firstOrCreateCanonical($yearNumber);
    }

    /**
     * Exam backfill: map legacy grade_id, YEAR in level string, then parse attached grade name.
     * Returns null when grade_id is null and no year could be resolved (leave unchanged).
     */
    public function resolveForExam(?int $currentGradeId, ?string $levelString): ?int
    {
        $resolved = $this->resolveGradeId($currentGradeId, $levelString);

        if ($resolved !== null) {
            return $resolved;
        }

        if ($currentGradeId === null) {
            return null;
        }

        $grade = Grade::query()->find($currentGradeId);

        if ($grade === null) {
            return null;
        }

        $yearNumber = GradeLabelParser::extractYearNumber($grade->name);

        if ($yearNumber === null) {
            return null;
        }

        return $this->firstOrCreateCanonical($yearNumber);
    }

    private function firstOrCreateCanonical(int $yearNumber): int
    {
        $grade = Grade::firstOrCreate(
            ['number' => $yearNumber],
            [
                'name' => Grade::nameFromNumber($yearNumber),
                'admin_id' => $this->adminId,
                'old_grades' => [],
            ]
        );

        $this->canonicalGradeIds[$grade->id] = $grade->number;

        return $grade->id;
    }

    public function wasMappedFromOldGrade(?int $originalGradeId, int $resolvedGradeId): bool
    {
        return $originalGradeId !== null
            && isset($this->oldGradeIdMap[$originalGradeId])
            && $this->oldGradeIdMap[$originalGradeId] === $resolvedGradeId
            && $originalGradeId !== $resolvedGradeId;
    }

    public function wasAlreadyCanonical(?int $originalGradeId, int $resolvedGradeId): bool
    {
        return $originalGradeId !== null
            && $originalGradeId === $resolvedGradeId
            && isset($this->canonicalGradeIds[$originalGradeId]);
    }
}
