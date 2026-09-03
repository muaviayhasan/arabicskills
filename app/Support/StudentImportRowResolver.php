<?php

namespace App\Support;

use App\Models\Grade;
use App\Models\Level;
use Throwable;

class StudentImportRowResolver
{
    private LevelLegacyResolver $levelResolver;

    private GradeLegacyResolver $gradeResolver;

    /** @var array<string, int> */
    private array $levelMap;

    /** @var array<string, int> */
    private array $gradeNameToId;

    public function __construct(
        ?LevelLegacyResolver $levelResolver = null,
        ?GradeLegacyResolver $gradeResolver = null,
        ?array $levelMap = null,
        ?array $gradeNameToId = null,
    ) {
        $this->levelResolver = $levelResolver ?? LevelLegacyResolver::make();
        $this->gradeResolver = $gradeResolver ?? GradeLegacyResolver::make();
        $this->levelMap = $levelMap ?? (new BackfillLevelIds)->buildLegacyMap();
        $this->gradeNameToId = $gradeNameToId ?? Grade::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Grade $grade) => [
                GradeLabelParser::normalize($grade->name) => $grade->id,
            ])
            ->toArray();
    }

    /**
     * Resolve level_id and grade_id from CSV column values without throwing.
     *
     * @return array{
     *     level_id: ?int,
     *     grade_id: ?int,
     *     level_name: ?string,
     *     grade_name: ?string,
     *     errors: string[]
     * }
     */
    public function resolve(?string $grade, ?string $level): array
    {
        $csvLevel = trim((string) ($level ?? ''));
        $csvGrade = trim((string) ($grade ?? ''));

        $result = [
            'level_id' => null,
            'grade_id' => null,
            'level_name' => null,
            'grade_name' => null,
            'errors' => [],
        ];

        try {
            if ($csvLevel !== '') {
                $result['level_id'] = $this->levelResolver->resolveFromImportValue($csvLevel, $this->levelMap);
            }

            $result['grade_id'] = $this->gradeResolver->resolveFromImportValue(
                $csvGrade !== '' ? $csvGrade : null,
                $csvLevel !== '' ? $csvLevel : null,
                $this->gradeNameToId
            );
        } catch (Throwable $e) {
            $result['errors'][] = 'Resolution error: '.$e->getMessage();

            return $result;
        }

        if ($result['level_id'] !== null) {
            $result['level_name'] = Level::query()->find($result['level_id'])?->name;
        }

        if ($result['grade_id'] !== null) {
            $result['grade_name'] = Grade::query()->find($result['grade_id'])?->name;
        }

        if ($csvLevel === '') {
            $result['errors'][] = 'Level is required.';
        } elseif ($result['level_id'] === null) {
            $result['errors'][] = "Unresolved level: {$csvLevel}";
        }

        if ($result['grade_id'] === null) {
            $label = $csvGrade !== '' ? $csvGrade : '(empty — no YEAR in level)';
            $result['errors'][] = "Unresolved grade: {$label}";
        }

        return $result;
    }

    public function isResolvable(?string $grade, ?string $level): bool
    {
        $resolved = $this->resolve($grade, $level);

        return $resolved['level_id'] !== null && $resolved['grade_id'] !== null;
    }
}
