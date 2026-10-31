<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A ceiling on what a student page may cost.
 *
 * Not a benchmark: the point is that none of these pages does work per
 * question, per activity or per media item. A page that starts querying in a
 * loop crosses these limits long before anyone notices it in a browser.
 */
class StudentPageQueryBudgetTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Every page a student can open, and the most queries it may take.
     *
     * @return array<string, int>
     */
    private const BUDGET = [
        '/student/dashboard' => 10,
        '/student/exams' => 20,
        '/student/instructions' => 10,
        '/student/exams/instruction/reading' => 10,
        '/student/exams/instruction/listening' => 10,
        '/student/exams/reading-exam' => 20,
        '/student/exams/listening-exam' => 20,
        '/student/exams/writing-exam' => 20,
        '/student/exams/speaking-exam' => 20,
        '/student/exams/sentences-structures-exam' => 20,
        '/student/device-test' => 10,
    ];

    private function student(): Student
    {
        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        return $student;
    }

    public function test_no_student_page_runs_more_queries_than_its_budget(): void
    {
        $student = $this->student();

        foreach (self::BUDGET as $page => $budget) {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $response = $this->actingAs($student, 'web')->get($page);

            $log = DB::getQueryLog();
            DB::disableQueryLog();

            $this->assertLessThan(500, $response->getStatusCode(), "{$page} failed with a server error");
            $this->assertLessThanOrEqual(
                $budget,
                count($log),
                sprintf('%s ran %d queries, budget is %d', $page, count($log), $budget)
            );
        }
    }

    /**
     * The shape an N+1 takes: one statement repeated. Reading an upload row per
     * image on the page used to look exactly like this.
     */
    public function test_no_student_page_repeats_the_same_statement_many_times(): void
    {
        $student = $this->student();

        foreach (array_keys(self::BUDGET) as $page) {
            DB::enableQueryLog();
            DB::flushQueryLog();

            $this->actingAs($student, 'web')->get($page);

            $log = DB::getQueryLog();
            DB::disableQueryLog();

            $counts = [];
            foreach ($log as $query) {
                $counts[$query['query']] = ($counts[$query['query']] ?? 0) + 1;
            }

            if ($counts === []) {
                continue;
            }

            arsort($counts);
            $worst = array_key_first($counts);

            $this->assertLessThanOrEqual(
                4,
                $counts[$worst],
                sprintf("%s ran one statement %d times:\n%s", $page, $counts[$worst], $worst)
            );
        }
    }
}
