<?php

namespace App\Support;

use App\Models\StudentExam;
use Carbon\Carbon;

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
     * @param  bool  $hasAnswers  whether the exam holds any answer at all
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
     * @return array<string, string>
     */
    public static function reopenableSkills(StudentExam $exam, bool $hasAnswers): array
    {
        $skills = [];

        foreach (self::SKILL_LABELS as $column => $label) {
            $status = $exam->{$column};

            if (is_array($status) && self::isReopenable($status, $hasAnswers)) {
                $skills[$column] = $label;
            }
        }

        return $skills;
    }
}
