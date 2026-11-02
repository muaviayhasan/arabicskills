<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Level;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use App\Support\UploadRules;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * What may be uploaded.
 *
 * A web shell reached this site at /uploads/cacheohhhpk.php. Uploads land in
 * storage/app/public, which is symlinked into the web root as /uploads, and
 * store() keeps the extension the file arrived with, so an upload the server
 * will execute is one the internet can run.
 *
 * Two doors were open: the question bank accepted 'nullable|file' for an
 * activity image and had no rules at all on the audio or the match options,
 * and the writing paper, which every student can reach, validated nothing.
 */
class UploadValidationTest extends TestCase
{
    use DatabaseTransactions;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /**
     * A file that is a PHP script however it is named.
     *
     * Deliberately a real file rather than UploadedFile::fake(), because the
     * fake reports a mime type guessed from its filename: a fake called
     * shell.png claims to be image/png whatever is inside it, so a test built
     * on one checks the fake instead of the rule. A real upload is sniffed
     * from its contents, which is the thing worth asserting.
     */
    private function phpFile(string $name = 'shell.php'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ast-upload-probe');
        file_put_contents($path, "<?php echo 'pwned'; ?>");
        $this->tempFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    private function realImage(): UploadedFile
    {
        return UploadedFile::fake()->image('page.jpg', 20, 20);
    }

    /** @param array<string, mixed> $data */
    private function passes(array $rules, array $data): bool
    {
        return Validator::make($data, $rules)->passes();
    }

    public function test_an_image_field_rejects_a_php_file(): void
    {
        $this->assertFalse($this->passes(['f' => UploadRules::image()], ['f' => $this->phpFile()]));
    }

    /**
     * Renaming it is the obvious next try. Laravel's `image` rule reads the
     * file rather than trusting the name, so this fails too.
     */
    public function test_an_image_field_rejects_a_php_file_renamed_as_an_image(): void
    {
        $this->assertFalse($this->passes(['f' => UploadRules::image()], ['f' => $this->phpFile('shell.png')]));
        $this->assertFalse($this->passes(['f' => UploadRules::image()], ['f' => $this->phpFile('shell.php.png')]));
        $this->assertFalse($this->passes(['f' => UploadRules::image()], ['f' => $this->phpFile('shell.png.php')]));
    }

    public function test_an_image_field_accepts_a_real_image(): void
    {
        $this->assertTrue($this->passes(['f' => UploadRules::image()], ['f' => $this->realImage()]));
        $this->assertTrue($this->passes(['f' => UploadRules::image()], ['f' => null]));
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        $huge = UploadedFile::fake()->image('big.jpg')->size(UploadRules::IMAGE_MAX_KB + 1);

        $this->assertFalse($this->passes(['f' => UploadRules::image()], ['f' => $huge]));
    }

    /**
     * A reading passage is text and a listening passage is an audio file, and
     * both arrive under the same key, so text has to pass and a script must
     * not.
     */
    public function test_a_passage_may_be_text_but_not_a_script(): void
    {
        $rules = ['f' => UploadRules::audioIfUploaded()];

        $this->assertTrue($this->passes($rules, ['f' => 'A paragraph of reading.']));
        $this->assertFalse($this->passes($rules, ['f' => $this->phpFile()]));
        $this->assertFalse($this->passes($rules, ['f' => $this->phpFile('audio.mp3')]));
    }

    /**
     * A match option is a word or a picture. These had no rules at all.
     */
    public function test_a_match_option_may_be_a_word_but_not_a_script(): void
    {
        $rules = ['f' => UploadRules::imageIfUploaded()];

        $this->assertTrue($this->passes($rules, ['f' => 'مدرسة']));
        $this->assertTrue($this->passes($rules, ['f' => $this->realImage()]));
        $this->assertFalse($this->passes($rules, ['f' => $this->phpFile()]));
        $this->assertFalse($this->passes($rules, ['f' => $this->phpFile('choice.jpg')]));
    }

    /**
     * The student-facing door: handing in written work. This is the one an
     * attacker needs only a student login to reach.
     */
    public function test_a_student_cannot_upload_a_script_as_their_written_work(): void
    {
        Storage::fake('local');

        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Upload Round',
            'status' => 'active',
        ]);

        $attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
        ]);

        $activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Upload Round',
            'title' => 'Writing activity',
            'activity' => 'Body',
            'type' => 'writing',
        ]);

        $question = Question::create([
            'activity_id' => $activity->id,
            'question' => 'Write about your school',
            'type' => 'writing',
        ]);

        $this->actingAs($student, 'web')
            ->postJson('/student/exams/writing-exam', [
                'exam_id' => $attempt->id,
                'answer' => [$question->id => ['answer' => [$this->phpFile()], 'type' => 'writing']],
            ])
            ->assertStatus(422);

        $this->assertSame(
            0,
            TakeExam::where('student_exam_id', $attempt->id)->count(),
            'nothing should be recorded when the upload is refused'
        );
    }

    public function test_a_student_may_still_hand_in_photographs_of_their_work(): void
    {
        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Upload Round Two',
            'status' => 'active',
        ]);

        $attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
        ]);

        $activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Upload Round Two',
            'title' => 'Writing activity',
            'activity' => 'Body',
            'type' => 'writing',
        ]);

        $question = Question::create([
            'activity_id' => $activity->id,
            'question' => 'Write about your school',
            'type' => 'writing',
        ]);

        $this->actingAs($student, 'web')
            ->postJson('/student/exams/writing-exam', [
                'exam_id' => $attempt->id,
                'answer' => [$question->id => ['answer' => [$this->realImage()], 'type' => 'writing']],
            ])
            ->assertOk();

        $this->assertSame(1, TakeExam::where('student_exam_id', $attempt->id)->count());
    }

    /**
     * The admin door, as the breach came through it.
     */
    public function test_the_question_bank_refuses_a_script_as_an_activity_image(): void
    {
        $admin = Admin::query()->first();
        $grade = Grade::query()->whereNotNull('number')->orderBy('number')->first();
        $level = Level::query()->orderBy('number')->first();

        if ($admin === null || $grade === null || $level === null) {
            $this->markTestSkipped('Need an admin, a numbered grade and a level.');
        }

        config(['admin_permissions' => ['question_banks' => ['view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1]]]);

        $response = $this->actingAs($admin, 'admin')->postJson('/adminino/add-question', [
            'activity' => [
                'type' => 'reading',
                'title' => 'Upload probe',
                'level_id' => $level->id,
                'grade_id' => $grade->id,
                'term' => 'Upload Probe Term',
                'lang' => 'english',
                'activity' => 'A paragraph.',
                'image' => $this->phpFile(),
            ],
            'question' => [['question' => 'A question']],
        ]);

        $this->assertContains(
            $response->getStatusCode(),
            [422, 403, 404, 405],
            'a php upload must not be accepted; got '.$response->getStatusCode()
        );

        if ($response->getStatusCode() === 422) {
            $this->assertArrayHasKey('activity.image', $response->json('errors') ?? []);
        }
    }
}
