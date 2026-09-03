<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Question;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

class QuestionBankController extends Controller
{
    public function setActivityType(Request $request)
    {
        try {

            $activityType = $request->input('activity_type');

            if ($activityType == 'listening') {
                $view = View::make('partials.audio-input', ['label' => 'Select File'])->render();
            } else {
                $view = View::make('partials.textarea', ['label' => 'Type Or Paste Paraphrase'])->render();
            }

            return response()->json(['html' => $view]);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function setQuestionType(Request $request)
    {
        try {
            $questionType = $request->input('questionType');
            $questionIndex = $request->input('question_index');
            $lang = $request->input('lang', 'english');

            $view = View::make("partials.question_types.{$questionType}", [
                'questionIndex' => $questionIndex,
                'lang' => $lang,
            ])->render();

            return response()->json(['html' => $view]);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function addQuestion(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'activity.type' => 'required|',
            'activity.title' => 'required',
            'activity.level_id' => 'required|exists:levels,id',
            'activity.grade_id' => ['required', Rule::exists('grades', 'id')->whereNotNull('number')],
            'activity.term' => 'required',
            'activity.lang' => 'required|in:english,arabic',
            'activity.image' => 'nullable|file',
            'activity.activity' => 'required',
            'question.*.question' => 'required',
            'question.*.image' => 'nullable|image|mimes:png,jpg,webp,jpeg',
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $pth = [];
            // Handle activity
            $activity = $this->prepareActivityAttributes($request->activity);

            if ($request->activity['type'] == 'listening' && $request->hasFile('activity.activity')) {
                $pth[] = $audio_path = $request->file('activity.activity')->store('public/images');
                $activity['activity'] = create_image(basename($audio_path));
            }

            if ($request->hasFile('activity.image')) {
                $image = $request->file('activity.image');
                $pth[] = $path = $image->store('public/images');

                $activity['image'] = create_image(basename($path));
            }

            $act = Activity::create($activity);

            // Handle questions
            $questions = $request->question;

            if (is_array($questions)) {
                foreach ($questions as $index => $qs) {
                    $options = null;

                    if ($qs['type'] == 'match') {

                        for ($i = 0; $i < 5; $i++) {

                            $choice = $qs['options']['choice'][$i] ?? null;
                            if (! is_null($choice) || ! empty($choice)) {
                                if ($request->hasFile("question.{$index}.options.choice.{$i}")) {

                                    $pth[] = $path = $choice->store('public/images');

                                    $qs['options']['choice'][$i] = (int) create_image($choice->hashName());
                                }
                            } else {
                                if (isset($qs['options']['choice'][$i])) {
                                    unset($qs['options']['choice'][$i]);
                                }
                            }

                            $answer = $qs['options']['answer'][$i] ?? null;

                            if (! is_null($answer) || ! empty($answer)) {
                                if ($request->hasFile("question.{$index}.options.answer.{$i}")) {

                                    $pth[] = $path = $answer->store('public/images');

                                    $qs['options']['answer'][$i] = create_image($answer->hashName());
                                }
                            } else {
                                if (isset($qs['options']['answer'][$i])) {
                                    unset($qs['options']['answer'][$i]);
                                }
                            }
                        }
                    } elseif ($qs['type'] == 'rearrange') {
                        $qs['question'] = implode(',', $qs['question']);
                    }
                    if (isset($qs['options'])) {
                        $options = serialize($qs['options']);
                    }

                    $question = Question::create([
                        'options' => $options ?? '',
                        'type' => $qs['type'],
                        'activity_id' => $act->id,
                        'question' => $qs['question'],
                        'correct_answer' => $qs['correct_answer'] ?? '',
                        'image' => null,
                    ]);

                    if ($request->hasFile("question.{$index}.image")) {
                        $image = $request->file("question.{$index}.image");
                        $pth[] = $path = $image->store('public/images');

                        $question->image = create_image(basename($path));
                    }

                    $question->save();
                }
            }

            DB::commit();

            return response()->json(['message' => 'Assessment added successfully. wish to Add more'], 200);
        } catch (Exception $e) {

            DB::rollback();

            foreach ($pth as $pa) {
                Storage::delete($pa);
            }

            return response()->json($e->getMessage(), 500);
        }
    }

    public function updateQuestion(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'activity.type' => 'required',
            'activity.activity' => 'nullable|required_unless:activity.type,listening',
            'activity.level_id' => 'required|exists:levels,id',
            'activity.grade_id' => ['required', Rule::exists('grades', 'id')->whereNotNull('number')],
            'activity.term' => 'required',
            'activity.lang' => 'required|in:english,arabic',
            'activity.title' => 'required',
            'activity.image' => 'nullable|image|mimes:png,jpg,webp,jpeg',

            'question.*.question' => 'required',
            'question.*.image' => 'nullable|image|mimes:png,jpg,webp,jpeg',
        ]);

        if ($validated->fails()) {
            return response()->json(['errors' => $validated->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $pth = [];

            $act = Activity::find($request->activity_id);

            $activity = $this->prepareActivityAttributes($request->activity, $act);

            if ($request->activity['type'] == 'listening') {
                if ($request->hasFile('activity.activity')) {

                    $pth[] = $audio_path = $request->file('activity.activity')->store('public/images');
                    $activity['activity'] = create_image(basename($audio_path));

                    delete_image($act->activity);
                }
            } else {

                if ($act->type == 'listening') {
                    delete_image($act->activity);
                }
            }

            if ($request->hasFile('activity.image')) {

                $image = $request->file('activity.image');
                $pth[] = $path = $image->store('public/images');

                if ($act->image) {
                    delete_image($act->image);
                }

                $activity['image'] = create_image(basename($path));
            }

            $act->update($activity);

            // Handle questions
            $questions = $request->question;

            $question = null;
            if (is_array($questions)) {
                foreach ($questions as $index => $qs) {

                    if (! empty($qs['id'])) {
                        $question = Question::where('id', $qs['id'])
                            ->where('activity_id', $act->id)
                            ->first();

                        if (! $question) {
                            $question = new Question([
                                'activity_id' => $act->id,
                                'type' => $qs['type'],
                                'image' => '',
                            ]);
                        }
                    } else {
                        $question = new Question([
                            'activity_id' => $act->id,
                            'type' => $qs['type'],
                            'image' => '',
                        ]);
                    }

                    if ($qs['type'] == 'match') {

                        $old = $question->options ?? [];

                        for ($i = 0; $i < 5; $i++) {

                            $choice = $qs['options']['choice'][$i] ?? null;
                            if (! is_null($choice) || ! empty($choice)) {
                                if ($request->hasFile("question.{$index}.options.choice.{$i}")) {

                                    $pth[] = $path = $choice->store('public/images');

                                    $qs['options']['choice'][$i] = (int) create_image($choice->hashName());
                                    delete_image($old['choice'][$i] ?? null);
                                } elseif (isset($old['choice'][$i]) && is_numeric($old['choice'][$i])) {

                                    delete_image($old['choice'][$i]);
                                }
                            } else {
                                if (isset($old['choice'][$i])) {
                                    $qs['options']['choice'][$i] = $old['choice'][$i];
                                } elseif (isset($qs['options']['choice'][$i])) {
                                    unset($qs['options']['choice'][$i]);
                                }
                            }

                            $answer = $qs['options']['answer'][$i] ?? null;

                            if (! is_null($answer) || ! empty($answer)) {
                                if ($request->hasFile("question.{$index}.options.answer.{$i}")) {

                                    $pth[] = $path = $answer->store('public/images');

                                    $qs['options']['answer'][$i] = create_image($answer->hashName());
                                    delete_image($old['answer'][$i] ?? null);
                                } elseif (isset($old['answer'][$i]) && is_numeric($old['answer'][$i])) {

                                    delete_image($old['answer'][$i]);
                                }
                            } else {
                                if (isset($old['answer'][$i])) {
                                    $qs['options']['answer'][$i] = $old['answer'][$i];
                                } elseif (isset($qs['options']['answer'][$i])) {
                                    unset($qs['options']['answer'][$i]);
                                }
                            }
                        }
                    } elseif ($qs['type'] == 'rearrange') {
                        $qs['question'] = implode(',', $qs['question']);
                    }
                    if (isset($qs['options'])) {
                        $question->options = serialize($qs['options']);
                    }

                    $question->question = $qs['question'];

                    if ($request->hasFile("question.{$index}.image")) {
                        $image = $request->file("question.{$index}.image");
                        $pth[] = $path = $image->store('public/images');

                        if ($question->image) {
                            delete_image($question->image);
                        }

                        $question->image = create_image(basename($path));
                    }
                    $question->correct_answer = $qs['correct_answer'] ?? '';

                    $question->save();
                }
            }

            DB::commit();

            return response()->json(['message' => 'Assessment updated successfully'], 200);
        } catch (Exception $e) {

            DB::rollback();
            foreach ($pth as $path) {
                Storage::delete($path);
            }

            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Normalize level_id and grade_id from the activity form payload.
     */
    protected function prepareActivityAttributes(array $activity, ?Activity $existing = null): array
    {
        $activity['level_id'] = (int) ($activity['level_id'] ?? 0);
        $activity['grade_id'] = (int) ($activity['grade_id'] ?? $existing?->grade_id ?? 0);

        return $activity;
    }
}
