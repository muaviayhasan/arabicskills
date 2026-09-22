<?php

namespace App\Models;

use App\Support\MarkRanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One student's marks for one round of one academic year.
 *
 * Judgments, the overall judgment and the expectation are worked out on demand
 * rather than stored, so changing a range on the Settings page updates every
 * past result too.
 */
class AssessmentMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'academic_year',
        'round',
        'reading',
        'listening',
        'writing',
        'speaking',
        'sentences_structures',
        'total',
        'admin_id',
    ];

    protected $casts = [
        'academic_year' => 'integer',
        'round' => 'integer',
        'reading' => 'float',
        'listening' => 'float',
        'writing' => 'float',
        'speaking' => 'float',
        'sentences_structures' => 'float',
        'total' => 'float',
    ];

    public function Student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function Admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * The five skill marks, keyed by column.
     *
     * @return array<string, float|null>
     */
    public function skillMarks(): array
    {
        $marks = [];

        foreach (array_keys(MarkRanges::SKILLS) as $skill) {
            $marks[$skill] = $this->{$skill};
        }

        return $marks;
    }

    public function judgementFor(string $skill): ?string
    {
        return MarkRanges::judge($this->{$skill}, MarkRanges::SKILL);
    }

    /** The judgment for the total, e.g. 68 out of 100 is "In line". */
    public function overallJudgement(): ?string
    {
        return MarkRanges::judge($this->total, MarkRanges::TOTAL);
    }

    /** What the student is expected to reach in the next round. */
    public function expectation(): ?string
    {
        return MarkRanges::nextExpectation($this->overallJudgement());
    }

    public function roundLabel(): string
    {
        return MarkRanges::roundLabel($this->round, $this->academic_year);
    }

    /**
     * The total of whichever skills have a mark. Absent skills are already
     * stored as 0, so they count; a round nobody sat is never saved at all.
     */
    public static function totalOf(array $marks): ?float
    {
        $present = array_filter(
            array_intersect_key($marks, MarkRanges::SKILLS),
            fn ($mark) => $mark !== null && $mark !== ''
        );

        return $present === [] ? null : (float) array_sum($present);
    }
}
