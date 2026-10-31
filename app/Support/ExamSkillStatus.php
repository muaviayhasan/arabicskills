<?php

namespace App\Support;

use App\Models\StudentExam;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamSkillStatus
{
    /**
     * The status column of each skill, and what to call it on screen.
     */
    public const SKILL_LABELS = [
        'reading_status' => 'Reading',
        'listening_status' => 'Listening',
        'writing_status' => 'Writing',
        'speaking_status' => 'Speaking',
        'sentences_structures_status' => 'Sentence structures',
    ];

    public static function withDeviceIp(array $status): array
    {
        if (! empty($status['device_ip'])) {
            return $status;
        }

        try {
            $ip = request()->ip();
            if (is_string($ip) && $ip !== '') {
                $status['device_ip'] = $ip;
            }
        } catch (\Throwable) {
            // Never block exam flow
        }

        return $status;
    }

    /**
     * Whether an admin reopening this skill would give the student a way back
     * in without costing them anything.
     *
     * A skill is theirs to sit while its status is unattempted and its window
     * is still open, so one that is merely in progress is left alone: a
     * student who crashed out can walk straight back into it.
     *
     * @param  bool  $hasAnswers  whether this skill holds recorded work. Per
     *                            skill, not per exam: a student who answered
     *                            reading and then had listening auto-submit
     *                            blank needs listening back, and an exam-wide
     *                            flag refused it because reading was answered.
     */
    public static function isReopenable(array $status, bool $hasAnswers): bool
    {
        $state = $status['status'] ?? 'unattempted';

        // Finished work is only reopened from the exam's own page, one skill
        // at a time, so a mis-click here can never overwrite it. Nothing was
        // recorded against an exam with no answers, so there is nothing to
        // protect: that is the paper that auto-submitted blank.
        if ($state === 'attempted') {
            return ! $hasAnswers;
        }

        if ($state === 'expired') {
            return true;
        }

        // A window that ran out while the student was away is still recorded
        // as unattempted: the status is only rewritten the next time someone
        // opens the exam.
        $endTime = $status['end_time'] ?? null;

        if (! is_string($endTime) || $endTime === '') {
            return false;
        }

        try {
            return Carbon::parse($endTime)->isPast();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * The skills of one exam that an admin can reopen, keyed by status column.
     *
     * @param  array<string, true>  $answeredSkills  status columns that hold
     *                                               recorded work, as
     *                                               answeredSkillsFor returns
     * @return array<string, string>
     */
    public static function reopenableSkills(StudentExam $exam, array $answeredSkills): array
    {
        $skills = [];

        foreach (self::SKILL_LABELS as $column => $label) {
            $status = $exam->{$column};

            if (is_array($status) && self::isReopenable($status, isset($answeredSkills[$column]))) {
                $skills[$column] = $label;
            }
        }

        return $skills;
    }

    /**
     * Which skills of these attempts hold recorded work, keyed by attempt id
     * and then by status column.
     *
     * An answer belongs to a question, a question to an activity, and the
     * activity carries the skill, so this join is the only way to tell reading
     * work from listening work. Counting rows on the exam instead treated any
     * answer anywhere as an answer everywhere.
     *
     * An empty answer is not work worth protecting: a paper that auto-submits
     * writes a blank row for every textarea on it, which is precisely the
     * paper an admin is trying to hand back.
     *
     * @param  list<int>  $studentExamIds
     * @return array<int, array<string, true>>
     */
    public static function answeredSkillsFor(array $studentExamIds): array
    {
        if ($studentExamIds === []) {
            return [];
        }

        $rows = DB::table('take_exams')
            ->join('questions', 'questions.id', '=', 'take_exams.question_id')
            ->join('activities', 'activities.id', '=', 'questions.activity_id')
            ->whereIn('take_exams.student_exam_id', $studentExamIds)
            ->whereNotNull('take_exams.answer')
            ->whereNotIn('take_exams.answer', ['', '{}'])
            ->groupBy('take_exams.student_exam_id', 'activities.type')
            ->select('take_exams.student_exam_id as attempt_id', 'activities.type as skill')
            ->get();

        $answered = [];

        foreach ($rows as $row) {
            $answered[(int) $row->attempt_id][$row->skill.'_status'] = true;
        }

        return $answered;
    }
}
