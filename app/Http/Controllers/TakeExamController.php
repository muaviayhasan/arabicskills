<?php

namespace App\Http\Controllers;

use App\Models\StudentExam;
use App\Models\TakeExam;
use App\Support\ExamSkillStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TakeExamController extends Controller
{
    public function saveSentencesStructuresExam(Request $request)
    {

        try {
            DB::beginTransaction();
            $student_exam = StudentExam::find($request->exam_id);
            $time = $student_exam->sentences_structures_status;
            $answers = $request->answer ?? [];
            foreach ($answers as $index => $ans) {
                TakeExam::updateOrCreate(
                    [
                        'student_exam_id' => $request->exam_id,
                        'question_id' => $index,
                        'student_id' => auth()->user()->id,
                    ],
                    [
                        'answer' => $ans['answer'] ?? '',
                        'type' => $ans['type'] ?? 'sentences_structures',
                    ]
                );
            }

            $time['status'] = 'attempted';
            $time['attempted_at'] = now()->format('Y-m-d H:i:s');
            $time = ExamSkillStatus::withDeviceIp($time);

            $student_exam->sentences_structures_status = $time;
            $student_exam->save();
            $student_exam->refresh();

            if ($student_exam->sentences_structures_status['status'] !== 'attempted') {
                throw new Exception('Failed to update exam status in database.');
            }

            DB::commit();

            return response()->json(['message' => 'Exam Submitted Successfully'], 200);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function saveReadExam(Request $request)
    {

        try {
            DB::beginTransaction();
            $student_exam = StudentExam::find($request->exam_id);
            $time = $student_exam->reading_status;
            $answers = $request->answer ?? [];
            foreach ($answers as $index => $ans) {
                TakeExam::updateOrCreate(
                    [
                        'student_exam_id' => $request->exam_id,
                        'question_id' => $index,
                        'student_id' => auth()->user()->id,
                    ],
                    [
                        'answer' => $ans['answer'] ?? '',
                        'type' => $ans['type'] ?? 'reading',
                    ]
                );
            }

            $time['status'] = 'attempted';
            $time['attempted_at'] = now()->format('Y-m-d H:i:s');
            $time = ExamSkillStatus::withDeviceIp($time);

            $student_exam->reading_status = $time;
            $student_exam->save();
            $student_exam->refresh();

            if ($student_exam->reading_status['status'] !== 'attempted') {
                throw new Exception('Failed to update exam status in database.');
            }

            DB::commit();

            return response()->json(['message' => 'Exam Submitted Successfully'], 200);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json($e->getMessage(), 500);
        }
    }

    // for listening exam
    public function saveListenExam(Request $request)
    {

        try {
            DB::beginTransaction();
            $student_exam = StudentExam::find($request->exam_id);
            $time = $student_exam->listening_status;
            $answers = $request->answer ?? [];
            foreach ($answers as $index => $ans) {
                TakeExam::updateOrCreate(
                    [
                        'student_exam_id' => $request->exam_id,
                        'question_id' => $index,
                        'student_id' => auth()->user()->id,
                    ],
                    [
                        'answer' => $ans['answer'] ?? '',
                        'type' => $ans['type'] ?? 'listening',
                    ]
                );
            }

            $time['status'] = 'attempted';
            $time['attempted_at'] = now()->format('Y-m-d H:i:s');
            $time = ExamSkillStatus::withDeviceIp($time);

            $student_exam->listening_status = $time;
            $student_exam->save();
            $student_exam->refresh();

            if ($student_exam->listening_status['status'] !== 'attempted') {
                throw new Exception('Failed to update exam status in database.');
            }

            DB::commit();

            return response()->json(['message' => 'Exam Submitted Successfully'], 200);
        } catch (Exception $e) {

            DB::rollback();

            return response()->json($e->getMessage(), 500);
        }
    }

    public function saveWritingExam(Request $request)
    {

        try {
            DB::beginTransaction();

            $student_exam = StudentExam::find($request->exam_id);
            $time = $student_exam->writing_status;

            $answers = $request->answer ?? [];
            $pth = [];
            foreach ($answers as $index => $ans) {

                if ($request->hasFile("answer.{$index}.answer.*")) {
                    $images = $request->file("answer.{$index}.answer");

                    $imgs = collect($images)->map(function ($image) {
                        $path = $image->store('public/images');

                        return create_image(basename($path));
                    });

                    $ans['answer'] = serialize($imgs->toArray());
                    $ans['type'] = 'writing';
                } else {
                    $ans['answer'] = $request->input("answer.{$index}.answer", '');
                }

                TakeExam::updateOrCreate([
                    'student_exam_id' => $request->exam_id,
                    'question_id' => $index,
                    'student_id' => auth()->user()->id,
                ], [
                    'answer' => $ans['answer'],
                    'type' => $ans['type'] ?? 'writing',
                ]);
            }

            $time['status'] = 'attempted';
            $time['attempted_at'] = now()->format('Y-m-d H:i:s');
            $time = ExamSkillStatus::withDeviceIp($time);

            $student_exam->writing_status = $time;
            $student_exam->save();
            $student_exam->refresh();

            if ($student_exam->writing_status['status'] !== 'attempted') {
                throw new Exception('Failed to update exam status in database.');
            }
            DB::commit();

            return response()->json(['message' => 'Exam Submitted Successfully'], 200);
        } catch (Exception $e) {
            \Log::error($e);
            DB::rollback();
            if (isset($pth) && is_array($pth)) {
                foreach ($pth as $path) {
                    Storage::delete($path);
                }
            }

            return response()->json($e->getMessage(), 500);
        }
    }

    public function saveSpeakingExam(Request $request)
    {
        try {
            DB::beginTransaction();

            $student_exam = StudentExam::findOrFail($request->exam_id);
            $time = $student_exam->speaking_status ?? [];
            $answers = $request->answer ?? [];

            foreach ($answers as $index => $ans) {
                $filename = '';

                if (
                    isset($ans['type']) &&
                    $ans['type'] === 'speaking' &&
                    ! empty($ans['answer'])
                ) {
                    // 1️⃣ Safe base64 decode
                    $audioData = base64_decode($ans['answer'], true);

                    if ($audioData === false) {
                        throw new Exception('Invalid audio data received.');
                    }

                    // 2️⃣ Detect MIME
                    $mime = $ans['mime'] ?? null;

                    if (empty($mime) && class_exists('finfo')) {
                        try {
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mime = $finfo->buffer($audioData);
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }

                    // 3️⃣ Decide extension
                    $ext = 'bin';
                    if (! empty($mime)) {
                        $m = strtolower($mime);
                        if (str_contains($m, 'audio/mp4') || str_contains($m, 'mp4')) {
                            $ext = 'm4a';
                        } elseif (str_contains($m, 'webm')) {
                            $ext = 'webm';
                        } elseif (str_contains($m, 'ogg') || str_contains($m, 'opus')) {
                            $ext = 'ogg';
                        } elseif (str_contains($m, 'mpeg') || str_contains($m, 'mp3')) {
                            $ext = 'mp3';
                        } elseif (str_contains($m, 'wav')) {
                            $ext = 'wav';
                        }
                    }

                    // 4️⃣ Generate filename
                    $filename = 'recorded_audio_'.$index.'_'.time().'.'.$ext;

                    // 5️⃣ Store audio
                    $saved = Storage::disk('public')->put('images/'.$filename, $audioData);

                    if (! $saved) {
                        throw new Exception('Failed to save audio file.');
                    }
                }

                // 6️⃣ Save answer
                TakeExam::updateOrCreate(
                    [
                        'student_exam_id' => $request->exam_id,
                        'question_id' => $index,
                        'student_id' => auth()->user()->id,
                    ],
                    [
                        'answer' => $filename ? create_image($filename) : '',
                        'type' => $ans['type'] ?? 'speaking',
                    ]
                );
            }

            // 7️⃣ Update exam status
            $time['status'] = 'attempted';
            $time['attempted_at'] = now()->format('Y-m-d H:i:s');
            $time = ExamSkillStatus::withDeviceIp($time);

            $student_exam->update([
                'speaking_status' => $time,
            ]);

            DB::commit();

            return response()->json(['message' => 'Exam Submitted Successfully'], 200);
        } catch (Exception $e) {
            DB::rollback();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
