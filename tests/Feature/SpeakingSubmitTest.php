<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\TakeExam;
use App\Models\Upload;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Handing in a speaking paper.
 *
 * Speaking is the only answer that arrives as base64 audio rather than a form
 * field, gets written to disk, and is then played back to a marker. Every
 * step of that is exercised here because a speaking session is what runs
 * next.
 */
class SpeakingSubmitTest extends TestCase
{
    use DatabaseTransactions;

    private Student $student;

    private StudentExam $attempt;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $student = Student::query()
            ->whereNotNull('level_id')->whereNotNull('grade_id')->whereNotNull('school_id')
            ->first();

        if ($student === null) {
            $this->markTestSkipped('Need a placed student.');
        }

        $this->student = $student;

        $activity = Activity::create([
            'level_id' => $student->level_id,
            'grade_id' => $student->grade_id,
            'term' => 'Speaking Round',
            'title' => 'Speaking activity',
            'activity' => 'Say something',
            'type' => 'speaking',
        ]);

        $this->question = Question::create([
            'activity_id' => $activity->id,
            'question' => 'Describe your school',
            'type' => 'speaking',
            'options' => serialize([]),
        ]);

        $exam = Exam::create([
            'school_id' => $student->school_id,
            'grade_id' => $student->grade_id,
            'level_ids' => [$student->level_id],
            'term' => 'Speaking Round',
            'status' => 'active',
            'speaking_activities' => [$activity->id],
            'speaking_time' => '00:40',
        ]);

        $this->attempt = StudentExam::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'checked' => false,
        ]);
    }

    /** A tiny but structurally real webm/ogg-ish payload. */
    private function audio(): string
    {
        return base64_encode("\x1a\x45\xdf\xa3".str_repeat("\x00\x01\x02\x03", 64));
    }

    private function submit(array $answer): TestResponse
    {
        return $this->actingAs($this->student, 'web')->postJson('/student/exams/speaking-exam', [
            'exam_id' => $this->attempt->id,
            'answer' => [$this->question->id => $answer],
        ]);
    }

    public function test_a_recording_is_stored_and_the_skill_marked_attempted(): void
    {
        Storage::fake('public');

        $this->submit(['answer' => $this->audio(), 'type' => 'speaking', 'mime' => 'audio/webm'])
            ->assertOk()
            ->assertJson(['message' => 'Exam Submitted Successfully']);

        $row = TakeExam::where('student_exam_id', $this->attempt->id)->first();

        $this->assertNotNull($row, 'the answer row should exist');
        $this->assertSame('speaking', $row->type);
        $this->assertNotSame('', (string) $row->answer, 'the answer should point at the stored audio');

        $upload = Upload::find($row->answer);
        $this->assertNotNull($upload, 'the answer should reference an uploads row');
        Storage::disk('public')->assertExists('images/'.$upload->path);

        $this->assertSame('attempted', $this->attempt->fresh()->speaking_status['status']);
    }

    /**
     * The extension is chosen from the mime the browser reports; different
     * browsers record different containers.
     */
    public function test_each_browser_container_lands_with_a_sane_extension(): void
    {
        Storage::fake('public');

        foreach ([
            'audio/webm' => 'webm',
            'audio/mp4' => 'm4a',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/wav' => 'wav',
        ] as $mime => $expected) {
            TakeExam::where('student_exam_id', $this->attempt->id)->delete();

            $this->submit(['answer' => $this->audio(), 'type' => 'speaking', 'mime' => $mime])->assertOk();

            $row = TakeExam::where('student_exam_id', $this->attempt->id)->first();
            $upload = Upload::find($row->answer);

            $this->assertStringEndsWith(
                '.'.$expected,
                (string) $upload?->path,
                "{$mime} should be stored as .{$expected}"
            );
        }
    }

    /** A browser that reports no mime still has its recording kept. */
    public function test_a_recording_with_no_mime_is_still_stored(): void
    {
        Storage::fake('public');

        $this->submit(['answer' => $this->audio(), 'type' => 'speaking'])->assertOk();

        $row = TakeExam::where('student_exam_id', $this->attempt->id)->first();
        $this->assertNotSame('', (string) $row->answer);
    }

    /** Rubbish in the audio field must not be written to disk as a file. */
    public function test_audio_that_is_not_base64_is_refused_cleanly(): void
    {
        Storage::fake('public');

        $this->submit(['answer' => '!!!not base64!!!', 'type' => 'speaking'])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        // and nothing of the paper was written
        $this->assertSame(0, TakeExam::where('student_exam_id', $this->attempt->id)->count());
        $this->assertNull($this->attempt->fresh()->speaking_status['status'] ?? null);
    }

    /** Submitting without opening the paper must not be a 500. */
    public function test_an_unknown_attempt_is_refused(): void
    {
        $this->actingAs($this->student, 'web')
            ->postJson('/student/exams/speaking-exam', [
                'exam_id' => 99999999,
                'answer' => [$this->question->id => ['answer' => $this->audio(), 'type' => 'speaking']],
            ])
            ->assertNotFound();
    }
}
