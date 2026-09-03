<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    /** Statuses shown on the student dashboard (skills list, exam details). */
    public const STUDENT_VISIBLE_STATUSES = ['active', 'pending'];

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'section_id',
        'grade_id',
        'level_ids',
        'listening_activities',
        'reading_activities',
        'speaking_activities',
        'sentences_structures_activities',
        'sentences_structures_time',
        'writing_activities',
        'listening_time',
        'reading_time',
        'speaking_time',
        'writing_time',
        'school_id',
        'time_allocated',
        'term',
        'status',
    ];

    protected $casts = [
        'level_ids' => 'array',
    ];

    protected function listeningActivities(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    protected function readingActivities(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    protected function sentencesStructuresActivities(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    protected function speakingActivities(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    protected function writingActivities(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => $value ? serialize($value) : serialize([]),
            get: fn ($value) => $value ? unserialize($value) : [],
        );
    }

    public function School()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function Section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function Grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function StudentExam()
    {
        return $this->hasMany(StudentExam::class, 'exam_id');
    }

    public function scopeApplyArchiveFilters(Builder $query, ?string $archiveStatus = null): Builder
    {
        $archiveStatus = $archiveStatus ?: 'active';

        if ($archiveStatus === 'archived') {
            $query->onlyTrashed();
        } elseif ($archiveStatus === 'all') {
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * @return list<int>
     */
    public function normalizedLevelIds(): array
    {
        $ids = $this->level_ids ?? [];

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', array_filter($ids, fn ($id) => $id !== null && $id !== ''))));
    }

    /**
     * Whether a grade-wide exam already exists for school + grade + term.
     */
    public static function existsForGradeTerm(
        int $schoolId,
        int $gradeId,
        string $term,
        ?string $level = null,
        ?int $excludeExamId = null
    ): bool {
        return static::query()
            ->where('school_id', $schoolId)
            ->where('grade_id', $gradeId)
            ->where('term', $term)
            ->whereNull('section_id')
            ->when($excludeExamId, fn (Builder $q) => $q->where('id', '!=', $excludeExamId))
            ->exists();
    }

    /**
     * Scope exams that apply to a student's level via level_ids JSON.
     */
    public static function applyStudentLevelScope(Builder $query, Student $student, string $table = 'exams'): Builder
    {
        if (! $student->level_id) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereJsonContains($table.'.level_ids', (int) $student->level_id);
    }

    /**
     * Whether the student has the minimum FK placement needed to resolve exams.
     */
    public static function studentHasResolvablePlacement(Student $student): bool
    {
        return (bool) ($student->school_id && $student->grade_id && $student->level_id);
    }

    /**
     * Pick the exam a student should use for one term among applicable candidates.
     * Grade-wide exams apply to all sections; section exams apply only to matching section.
     * When multiple apply, the latest created_at wins.
     */
    public static function pickResolvedFromCandidates(Collection $candidates, Student $student): ?self
    {
        return $candidates
            ->filter(function (self $exam) use ($student) {
                if ($exam->section_id === null) {
                    return true;
                }

                return $student->section_id
                    && (int) $exam->section_id === (int) $student->section_id;
            })
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Base candidate exams for a student (school + grade + level + statuses).
     */
    public static function candidateQueryForStudent(Student $student, array $statuses): Builder
    {
        $query = static::query()
            ->where('school_id', $student->school_id)
            ->where('grade_id', $student->grade_id)
            ->whereIn('status', $statuses);

        static::applyStudentLevelScope($query, $student);

        return $query->orderByDesc('created_at');
    }

    /**
     * Resolved exam for a student and optional term.
     */
    public static function resolveForStudent(Student $student, array $statuses, ?string $term = null): ?self
    {
        if ($term !== null) {
            $candidates = static::candidateQueryForStudent($student, $statuses)
                ->where('term', $term)
                ->get();

            return static::pickResolvedFromCandidates($candidates, $student);
        }

        return static::resolveLatestForStudent($student, $statuses);
    }

    /**
     * One resolved exam per term for a student.
     */
    public static function resolvedExamsForStudent(Student $student, array $statuses): Collection
    {
        return static::candidateQueryForStudent($student, $statuses)
            ->get()
            ->groupBy('term')
            ->map(fn (Collection $group) => static::pickResolvedFromCandidates($group, $student))
            ->filter()
            ->values();
    }

    /**
     * Latest resolved exam (by created_at) for attempt flows.
     */
    public static function resolveLatestForStudent(Student $student, array $statuses): ?self
    {
        return static::resolvedExamsForStudent($student, $statuses)
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * Students who should be linked to this exam when syncing.
     * Matches school + grade + level; section exams also filter by section.
     */
    public function eligibleStudentsQuery(): Builder
    {
        $query = Student::query()
            ->where('school_id', $this->school_id)
            ->where('grade_id', $this->grade_id);

        $levelIds = $this->normalizedLevelIds();

        if ($levelIds !== []) {
            $query->whereIn('level_id', $levelIds);
        }

        if ($this->section_id) {
            $query->where('section_id', $this->section_id);
        }

        return $query;
    }

    /**
     * Link eligible students to this exam (section-specific or grade-wide).
     * Reconciles each eligible student so only resolved exam links remain per term.
     */
    public function syncStudentExams(): int
    {
        if (empty($this->school_id) || empty($this->grade_id)) {
            return 0;
        }

        if ($this->normalizedLevelIds() === []) {
            return 0;
        }

        $reconciled = 0;

        $this->eligibleStudentsQuery()
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($students) use (&$reconciled) {
                foreach ($students as $student) {
                    static::reconcileStudentExams($student);
                    $reconciled++;
                }
            });

        return $reconciled;
    }

    /**
     * Remove exact duplicate rows for the same student + exam pair (keeps lowest id).
     */
    public static function dedupeStudentExamPairs(?int $studentId = null): int
    {
        $duplicateGroups = DB::table('student_exams')
            ->select('student_id', 'exam_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
            ->when($studentId !== null, fn ($query) => $query->where('student_id', $studentId))
            ->groupBy('student_id', 'exam_id')
            ->having('total', '>', 1)
            ->get();

        $deleted = 0;

        foreach ($duplicateGroups as $group) {
            $deleted += StudentExam::query()
                ->where('student_id', $group->student_id)
                ->where('exam_id', $group->exam_id)
                ->where('id', '!=', $group->keep_id)
                ->delete();
        }

        return $deleted;
    }

    /**
     * Ensure a student has student_exams rows for resolved exams and remove stale unchecked links.
     */
    public static function reconcileStudentExams(Student $student): void
    {
        static::dedupeStudentExamPairs($student->id);

        if (! static::studentHasResolvablePlacement($student)) {
            StudentExam::query()
                ->where('student_id', $student->id)
                ->where('checked', false)
                ->delete();

            return;
        }

        $visibleExams = static::resolvedExamsForStudent($student, static::STUDENT_VISIBLE_STATUSES);
        $resolvedExamIds = $visibleExams->pluck('id')->all();

        foreach ($visibleExams as $exam) {
            StudentExam::firstOrCreate([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
            ]);
        }

        StudentExam::query()
            ->where('student_id', $student->id)
            ->where('checked', false)
            ->where(function (Builder $query) use ($student, $resolvedExamIds) {
                $query->whereHas('Exam', fn (Builder $examQuery) => $examQuery->onlyTrashed())
                    ->orWhereHas('Exam', function (Builder $examQuery) use ($student) {
                        $examQuery->where(function (Builder $placement) use ($student) {
                            $placement->where('school_id', '!=', $student->school_id)
                                ->orWhere('grade_id', '!=', $student->grade_id);
                        });
                    })
                    ->orWhere(function (Builder $stale) use ($student, $resolvedExamIds) {
                        $stale->whereHas('Exam', function (Builder $examQuery) use ($student) {
                            $examQuery->whereIn('status', static::STUDENT_VISIBLE_STATUSES)
                                ->where('school_id', $student->school_id)
                                ->where('grade_id', $student->grade_id);
                            static::applyStudentLevelScope($examQuery, $student, 'exams');
                        });

                        if ($resolvedExamIds !== []) {
                            $stale->whereNotIn('exam_id', $resolvedExamIds);
                        }
                    });
            })
            ->delete();
    }
}
