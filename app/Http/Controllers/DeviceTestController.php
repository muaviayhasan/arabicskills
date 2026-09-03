<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\DeviceTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeviceTestController extends Controller
{
    /**
     * Process the device test form submission.
     *
     * Validates each answer type:
     * - MCQs, true-false: answer must be a non-empty string
     * - blanks, typing: answer must be a non-empty string (text)
     * - rearrange, match, match_words: answer must be non-empty and not '{}'
     * - speaking: answer must be a valid base64 audio string
     * - writing (typing mode): answer must be a non-empty string
     * - writing (upload mode): answer must be uploaded file(s)
     */
    public function submitTest(Request $request)
    {
        try {
            DB::beginTransaction();

            $answers = $request->answer ?? [];
            $deviceInfo = $request->device_info ? json_decode($request->device_info, true) : [];
            $testResults = [];

            foreach ($answers as $questionId => $ans) {
                $type = $ans['type'] ?? '';
                $result = [
                    'id' => $questionId,
                    'type' => $type,
                    'status' => 'failed',
                ];

                switch ($type) {
                    case 'MCQs':
                    case 'true-false':
                        // Check if a radio option was selected
                        $answer = $ans['answer'] ?? '';
                        $result['answer'] = $answer;
                        if (!empty(trim($answer))) {
                            $result['status'] = 'passed';
                        }
                        break;

                    case 'blanks':
                    case 'typing':
                        // Check if text was typed in the textarea or files were uploaded
                        $answer = $ans['answer'] ?? '';
                        
                        if (is_array($answer)) {
                            // If it's an array, it might be files from writing-answer
                            if (count(array_filter($answer)) > 0 || $request->hasFile("answer.{$questionId}.answer")) {
                                $result['status'] = 'passed';
                            }
                            
                            // Save file names to show to the user
                            if ($request->hasFile("answer.{$questionId}.answer")) {
                                $files = $request->file("answer.{$questionId}.answer");
                                $fileNames = [];
                                foreach ($files as $f) {
                                    $fileNames[] = $f->getClientOriginalName();
                                }
                                $result['answer'] = "Uploaded files: " . implode(', ', $fileNames);
                            } else {
                                $result['answer'] = json_encode($answer);
                            }
                        } else {
                            $result['answer'] = $answer;
                            if (!empty(trim($answer))) {
                                $result['status'] = 'passed';
                            }
                        }

                        // Also explicitly check if there are files attached to this question ID
                        if ($request->hasFile("answer.{$questionId}.answer")) {
                            $result['status'] = 'passed';
                        }
                        break;

                    case 'rearrange':
                    case 'match':
                    case 'match_words':
                        // Check if the hidden input has data (not empty or '{}')
                        $answer = $ans['answer'] ?? '';
                        $result['answer'] = $answer;
                        $trimmed = trim($answer);
                        if (!empty($trimmed) && $trimmed !== '{}' && $trimmed !== '[]') {
                            $result['status'] = 'passed';
                        }
                        break;

                    case 'speaking':
                    case 'speaking-answer':
                        // Check if base64 audio data was submitted
                        $answer = $ans['answer'] ?? '';
                        if (!empty($answer)) {
                            $audioData = base64_decode($answer, true);

                            if ($audioData !== false && strlen($audioData) > 100) {
                                $result['status'] = 'passed';
                                $result['answer'] = 'Recording was verified successfully!';
                            }
                        }
                        break;

                    case 'writing':
                    case 'writing-answer':
                        // Writing can be submitted as file upload OR keyboard text
                        // Check for file upload first
                        if ($request->hasFile("answer.{$questionId}.answer")) {
                            $files = $request->file("answer.{$questionId}.answer");
                            // Could be array of files (multiple) or single
                            if (is_array($files) && count($files) > 0) {
                                $result['status'] = 'passed';
                                
                                $fileNames = [];
                                foreach ($files as $f) {
                                    $fileNames[] = $f->getClientOriginalName();
                                }
                                $result['answer'] = "Uploaded files: " . implode(', ', $fileNames);
                            }
                        } else {
                            // Check for keyboard text
                            $answer = $request->input("answer.{$questionId}.answer", '');
                            $result['answer'] = $answer;
                            if (!empty(trim($answer))) {
                                $result['status'] = 'passed';
                            }
                        }
                        break;

                    default:
                        // Unknown type — mark as failed
                        break;
                }

                $testResults[] = $result;
            }

            // Determine overall status
            $allPassed = collect($testResults)->every(fn($r) => $r['status'] === 'passed');
            $overallStatus = $allPassed ? 'passed' : 'failed';

            // Save to database
            $deviceTest = DeviceTest::create([
                'student_id' => auth()->user()->id,
                'device_info' => $deviceInfo,
                'test_results' => $testResults,
                'overall_status' => $overallStatus,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Device test completed successfully!',
                'test_id' => $deviceTest->id,
                'redirect' => route('student.device-test.result', $deviceTest->id),
            ], 200);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
