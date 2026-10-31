<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\StudentExam;
use App\Models\TakeExam;
use App\Support\ExamSkillStatus;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TakeExamController extends Controller
{
    /**
     * take_exams.type is a MySQL enum and the session runs in strict mode, so
     * a value outside this list does not coerce, it errors, and the rollback
     * takes the whole paper with it. The old per-skill defaults ('reading',
     * 'listening', 'sentences_structures') are not enum members, so an answer
     * arriving without a type would have failed the submit; nothing in
     * take_exams carries those values, so no client has ever done it.
     */
    private const ANSWER_TYPES = [
        'MCQs', 'true-false', 'blanks', 'typing',
        'rearrange', 'match', 'match_words', 'writing', 'speaking',
    ];

    public function saveReadExam(Request $request): JsonResponse
    {
        return $this->save($request, 'reading_status');
    }

    public function saveListenExam(Request $request): JsonResponse
    {
        return $this->save($request, 'listening_status');
    }

    public function saveSentencesStructuresExam(Request $request): JsonResponse
    {
        return $this->save($request, 'sentences_structures_status');
    }

    public function saveWritingExam(Request $request): JsonResponse
    {
        return $this->save($request, 'writing_status', $this->writingAnswer());
    }

    public function saveSpeakingExam(Request $request): JsonResponse
    {
        return $this->save($request, 'speaking_status', $this->speakingAnswer());
    }

    /**
     * The attempt this submission is for.
     *
     * exam_id arrives as a hidden field, so it is whatever the browser sends.
     * Reading it straight into find() meant a student could post answers
     * against somebody else's attempt and mark their paper submitted, and a
     * stale or missing id returned null, whereupon reading a status off it
     * raised an Error rather than an Exception: not caught, no rollback, a 500
     * instead of json, and the paper lost behind "an unexpected error".
     */
    private function resolveAttempt(Request $request): ?StudentExam
    {
        $id = $request->input('exam_id');

        if (! is_numeric($id)) {
            return null;
        }

        return StudentExam::query()
            ->whereKey((int) $id)
            ->where('student_id', auth()->guard('web')->id())
            ->where('checked', false)
            ->first();
    }

    /**
     * Shared submit flow: resolve the attempt, write the answers, mark the
     * skill attempted.
     *
     * @param  (callable(Request, int|string, array<string, mixed>): array{answer: mixed, type: string|null})|null  $prepare
     */
    private function save(Request $request, string $statusColumn, ?callable $prepare = null): JsonResponse
    {
        // Outside the try: a 404 raised inside it would be caught as an
        // Exception and returned as a 500.
        $attempt = $this->resolveAttempt($request);

        if ($attempt === null) {
            return response()->json([
                'message' => 'This exam is no longer open for submission. Please reload the page.',
            ], 404);
        }

        $storedPaths = [];

        try {
            DB::beginTransaction();

            $prepared = [];

            foreach ($request->input('answer') ?? [] as $questionId => $answer) {
                $answer = is_array($answer) ? $answer : ['answer' => $answer];

                $prepared[$questionId] = $prepare === null
                    ? ['answer' => $answer['answer'] ?? '', 'type' => $answer['type'] ?? null]
                    : $prepare($request, $questionId, $answer, $storedPaths);
            }

            $this->recordAnswers($attempt, $prepared);

            $status = $attempt->{$statusColumn} ?? [];
            $status['status'] = 'attempted';
            $status['attempted_at'] = now()->format('Y-m-d H:i:s');

            $attempt->{$statusColumn} = ExamSkillStatus::withDeviceIp($status);
            $attempt->save();
            $attempt->refresh();

            if (($attempt->{$statusColumn}['status'] ?? null) !== 'attempted') {
                throw new Exception('Failed to update exam status in database.');
            }

            DB::commit();

            return response()->json(['message' => 'Exam Submitted Successfully'], 200);
        } catch (\Throwable $e) {
            DB::rollback();

            // The files were written outside the transaction, so they survive
            // the rollback unless they are removed here. The old code built
            // this list but never added to it, so nothing was ever cleaned up.
            foreach ($storedPaths as $path) {
                Storage::delete($path);
            }

            Log::error('Exam submission failed', [
                'student_exam_id' => $attempt->id,
                'skill' => $statusColumn,
                'exception' => $e,
            ]);

            // Not $e->getMessage(): that handed the browser raw SQL and file
            // paths on any failure.
            return response()->json([
                'message' => 'Your answers could not be saved. Please try again.',
            ], 500);
        }
    }

    /**
     * Write the answers for one paper.
     *
     * This was an updateOrCreate per answer, so a 32 question paper cost 67
     * queries and about 350ms, all of it landing at the moment a room full of
     * students runs out of time at once. A first submit is two queries now.
     *
     * @param  array<int|string, array{answer: mixed, type: string|null}>  $answers
     */
    private function recordAnswers(StudentExam $attempt, array $answers): void
    {
        if ($answers === []) {
            return;
        }

        $questionIds = array_keys($answers);

        $existing = TakeExam::query()
            ->where('student_exam_id', $attempt->id)
            ->whereIn('question_id', $questionIds)
            ->pluck('id', 'question_id');

        $fallbackTypes = Question::query()
            ->whereIn('id', $questionIds)
            ->pluck('type', 'id');

        $now = now();
        $insert = [];

        foreach ($answers as $questionId => $answer) {
            $row = [
                'answer' => $answer['answer'] ?? '',
                'type' => $this->answerType($answer['type'] ?? null, $fallbackTypes[$questionId] ?? null),
                'updated_at' => $now,
            ];

            if (isset($existing[$questionId])) {
                TakeExam::query()->whereKey($existing[$questionId])->update($row);

                continue;
            }

            $insert[] = $row + [
                'student_exam_id' => $attempt->id,
                'question_id' => $questionId,
                'student_id' => $attempt->student_id,
                'created_at' => $now,
            ];
        }

        if ($insert !== []) {
            TakeExam::query()->insert($insert);
        }
    }

    /**
     * A type the enum will accept: what the browser sent if it is a member,
     * otherwise the type the question itself carries.
     */
    private function answerType(?string $sent, ?string $questionType): string
    {
        foreach ([$sent, $questionType] as $candidate) {
            if ($candidate !== null && in_array($candidate, self::ANSWER_TYPES, true)) {
                return $candidate;
            }
        }

        return 'MCQs';
    }

    /**
     * Writing answers are either uploaded images or typed text.
     */
    private function writingAnswer(): callable
    {
        return function (Request $request, $questionId, array $answer, array &$storedPaths): array {
            if ($request->hasFile("answer.{$questionId}.answer.*")) {
                $images = collect($request->file("answer.{$questionId}.answer"))
                    ->map(function ($image) use (&$storedPaths) {
                        $path = $image->store('public/images');
                        $storedPaths[] = $path;

                        return create_image(basename($path));
                    });

                return ['answer' => serialize($images->toArray()), 'type' => 'writing'];
            }

            return [
                'answer' => $request->input("answer.{$questionId}.answer", ''),
                'type' => 'writing',
            ];
        };
    }

    /**
     * Speaking answers arrive as base64 audio and are stored as a file.
     */
    private function speakingAnswer(): callable
    {
        return function (Request $request, $questionId, array $answer, array &$storedPaths): array {
            if (($answer['type'] ?? null) !== 'speaking' || empty($answer['answer'])) {
                return ['answer' => '', 'type' => 'speaking'];
            }

            $audio = base64_decode((string) $answer['answer'], true);

            if ($audio === false) {
                throw new Exception('Invalid audio data received.');
            }

            $filename = 'recorded_audio_'.$questionId.'_'.time().'.'.$this->audioExtension($answer, $audio);
            $path = 'images/'.$filename;

            if (! Storage::disk('public')->put($path, $audio)) {
                throw new Exception('Failed to save audio file.');
            }

            $storedPaths[] = 'public/'.$path;

            return ['answer' => create_image($filename), 'type' => 'speaking'];
        };
    }

    /**
     * @param  array<string, mixed>  $answer
     */
    private function audioExtension(array $answer, string $audio): string
    {
        $mime = $answer['mime'] ?? null;

        if (empty($mime) && class_exists('finfo')) {
            try {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($audio);
            } catch (\Throwable) {
                // fall through to the default
            }
        }

        $mime = strtolower((string) $mime);

        return match (true) {
            str_contains($mime, 'mp4') => 'm4a',
            str_contains($mime, 'webm') => 'webm',
            str_contains($mime, 'ogg'), str_contains($mime, 'opus') => 'ogg',
            str_contains($mime, 'mpeg'), str_contains($mime, 'mp3') => 'mp3',
            str_contains($mime, 'wav') => 'wav',
            default => 'bin',
        };
    }
}
