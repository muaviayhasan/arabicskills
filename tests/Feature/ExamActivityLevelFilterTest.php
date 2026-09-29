<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\Student;
use App\Support\ExamActivityQuery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * An exam can be created for several levels at once, and the activity list it
 * stores is flat, so it holds every level's activities together. Each student
 * must be served only the activities for their own level.
 */
class ExamActivityLevelFilterTest extends TestCase
{
    use DatabaseTransactions;

    private Grade $grade;

    private Level $levelOne;

    private Level $levelTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $grade = Grade::query()->whereNotNull('number')->orderBy('number')->first();
        $levels = Level::query()->orderBy('number')->limit(2)->get();

        if ($grade === null || $levels->count() < 2) {
            $this->markTestSkipped('Need one numbered grade and two levels.');
        }

        $this->grade = $grade;
        $this->levelOne = $levels->first();
        $this->levelTwo = $levels->last();
    }

    private function makeActivity(?int $levelId, string $type = 'reading'): Activity
    {
        return Activity::create([
            'level_id' => $levelId,
            'grade_id' => $this->grade->id,
            'term' => 'Round One 2026 - 2027',
            'title' => 'Test activity',
            'activity' => 'Test activity body',
            'type' => $type,
        ]);
    }

    /**
     * Unsaved on purpose: the filter only reads the stored activity list and
     * the exam's levels, so there is no need to satisfy the exams table.
     *
     * @param  list<int>  $levelIds
     * @param  list<int>  $activityIds
     */
    private function makeExam(array $levelIds, array $activityIds, string $type = 'reading'): Exam
    {
        $exam = new Exam(['level_ids' => $levelIds]);
        $exam->{$type.'_activities'} = $activityIds;

        return $exam;
    }

    private function makeStudent(?int $levelId): Student
    {
        return new Student(['grade_id' => $this->grade->id, 'level_id' => $levelId]);
    }

    public function test_a_student_is_served_only_their_own_level(): void
    {
        $one = $this->makeActivity($this->levelOne->id);
        $two = $this->makeActivity($this->levelTwo->id);

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$one->id, $two->id]
        );

        $this->assertSame(
            [$one->id],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelOne->id), 'reading')
        );

        $this->assertSame(
            [$two->id],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelTwo->id), 'reading')
        );
    }

    /**
     * 187 activities pre-date level tagging and are still attached to older
     * exams. Dropping them would leave those papers empty.
     */
    public function test_activities_with_no_level_recorded_are_kept(): void
    {
        $untagged = $this->makeActivity(null);
        $other = $this->makeActivity($this->levelTwo->id);

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$untagged->id, $other->id]
        );

        $this->assertSame(
            [$untagged->id],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelOne->id), 'reading')
        );
    }

    /**
     * A single-level exam cannot mix levels, so it is left exactly as it is.
     * Some older exams have a stray activity from another level attached; they
     * have already been sat and marked, and must not change now.
     */
    public function test_a_single_level_exam_is_never_filtered(): void
    {
        $stray = $this->makeActivity($this->levelTwo->id);
        $own = $this->makeActivity($this->levelOne->id);

        $exam = $this->makeExam([$this->levelOne->id], [$own->id, $stray->id]);

        $this->assertSame(
            [$own->id, $stray->id],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelOne->id), 'reading')
        );
    }

    /**
     * The stored order is the order the paper is sat in.
     */
    public function test_the_stored_order_is_preserved(): void
    {
        $first = $this->makeActivity($this->levelOne->id);
        $other = $this->makeActivity($this->levelTwo->id);
        $second = $this->makeActivity($this->levelOne->id);
        $third = $this->makeActivity($this->levelOne->id);

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$third->id, $other->id, $first->id, $second->id]
        );

        $this->assertSame(
            [$third->id, $first->id, $second->id],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelOne->id), 'reading')
        );
    }

    /**
     * A student with no level cannot resolve an exam at all, so this only
     * guards the admin screens, where showing nothing would be worse.
     */
    public function test_a_student_without_a_level_falls_back_to_the_whole_list(): void
    {
        $one = $this->makeActivity($this->levelOne->id);
        $two = $this->makeActivity($this->levelTwo->id);

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$one->id, $two->id]
        );

        $this->assertSame(
            [$one->id, $two->id],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent(null), 'reading')
        );

        $this->assertSame(
            [$one->id, $two->id],
            ExamActivityQuery::activityIdsForStudent($exam, null, 'reading')
        );
    }

    public function test_a_skill_with_no_activities_returns_nothing(): void
    {
        $exam = $this->makeExam([$this->levelOne->id, $this->levelTwo->id], []);

        $this->assertSame(
            [],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelOne->id), 'reading')
        );
    }

    /**
     * A level whose activities are all in another level gets an empty paper
     * rather than someone else's.
     */
    public function test_a_level_with_no_activities_of_its_own_gets_nothing(): void
    {
        $two = $this->makeActivity($this->levelTwo->id);

        $exam = $this->makeExam([$this->levelOne->id, $this->levelTwo->id], [$two->id]);

        $this->assertSame(
            [],
            ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($this->levelOne->id), 'reading')
        );
    }

    public function test_each_skill_is_filtered_independently(): void
    {
        $reading = $this->makeActivity($this->levelOne->id, 'reading');
        $listeningOther = $this->makeActivity($this->levelTwo->id, 'listening');
        $listeningOwn = $this->makeActivity($this->levelOne->id, 'listening');

        $exam = $this->makeExam([$this->levelOne->id, $this->levelTwo->id], [$reading->id], 'reading');
        $exam->listening_activities = [$listeningOther->id, $listeningOwn->id];

        $student = $this->makeStudent($this->levelOne->id);

        $this->assertSame([$reading->id], ExamActivityQuery::activityIdsForStudent($exam, $student, 'reading'));
        $this->assertSame([$listeningOwn->id], ExamActivityQuery::activityIdsForStudent($exam, $student, 'listening'));
    }

    /**
     * The admin preview must show exactly what a student on that level sits.
     */
    public function test_previewing_a_level_matches_what_that_level_is_served(): void
    {
        $one = $this->makeActivity($this->levelOne->id);
        $two = $this->makeActivity($this->levelTwo->id);

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$one->id, $two->id]
        );

        foreach ([$this->levelOne, $this->levelTwo] as $level) {
            $this->assertSame(
                ExamActivityQuery::activityIdsForStudent($exam, $this->makeStudent($level->id), 'reading'),
                ExamActivityQuery::activityIdsForLevel($exam, $level->id, 'reading')
            );
        }
    }

    /**
     * The exam card shows one total across every level, which says nothing
     * about whether a given level has a paper to sit.
     */
    public function test_coverage_flags_a_level_that_is_short_of_a_skill(): void
    {
        $readingOne = $this->makeActivity($this->levelOne->id, 'reading');
        $readingTwo = $this->makeActivity($this->levelTwo->id, 'reading');
        $listeningOne = $this->makeActivity($this->levelOne->id, 'listening');

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$readingOne->id, $readingTwo->id],
            'reading'
        );
        $exam->listening_activities = [$listeningOne->id];

        $coverage = ExamActivityQuery::levelCoverage($exam, ['reading', 'listening']);

        $this->assertCount(2, $coverage);
        $this->assertSame([], $coverage[0]['missing']);
        $this->assertSame(['listening'], $coverage[1]['missing']);
        $this->assertSame(1, $coverage[0]['counts']['reading']);
        $this->assertSame(0, $coverage[1]['counts']['listening']);
    }

    /**
     * A skill the exam leaves out for everybody is a choice, not a level being
     * short-changed, and must not raise a warning on every level.
     */
    public function test_coverage_ignores_a_skill_the_exam_does_not_carry_at_all(): void
    {
        $readingOne = $this->makeActivity($this->levelOne->id, 'reading');
        $readingTwo = $this->makeActivity($this->levelTwo->id, 'reading');

        $exam = $this->makeExam(
            [$this->levelOne->id, $this->levelTwo->id],
            [$readingOne->id, $readingTwo->id]
        );

        $coverage = ExamActivityQuery::levelCoverage($exam, ['reading', 'writing']);

        foreach ($coverage as $row) {
            $this->assertSame([], $row['missing']);
        }
    }

    public function test_coverage_is_empty_for_a_single_level_exam(): void
    {
        $activity = $this->makeActivity($this->levelOne->id);
        $exam = $this->makeExam([$this->levelOne->id], [$activity->id]);

        $this->assertSame([], ExamActivityQuery::levelCoverage($exam, ['reading']));
    }

    /**
     * Coverage has to agree with what students are served, untagged activities
     * included, or the admin is told one thing and the student sits another.
     */
    public function test_coverage_counts_untagged_activities_for_every_level(): void
    {
        $untagged = $this->makeActivity(null);

        $exam = $this->makeExam([$this->levelOne->id, $this->levelTwo->id], [$untagged->id]);

        foreach (ExamActivityQuery::levelCoverage($exam, ['reading']) as $row) {
            $this->assertSame(1, $row['counts']['reading']);
            $this->assertSame([], $row['missing']);
        }
    }
}
