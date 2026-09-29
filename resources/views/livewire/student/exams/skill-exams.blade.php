<div class="student-skill-exams">
    <x-std-header :title="'Exams <span>الاختبارات</span>'" />

    <section class="student-info mt-4">
        <div class="container">
            <div class="skill-exams-intro text-center mb-4">
                <h4 class="fw-bold mb-2">Your Skill Exams</h4>
                <p class="text-muted mx-auto mb-0" style="max-width: 560px;">
                    Each skill has its own timed section. Complete all assigned skills before the exam window closes.
                </p>
            </div>

            @if ($latestExam)
                @php
                    $exam = $latestExam->Exam;
                    $isGradeBased = empty($exam?->section_id);
                    $statusBadgeClass = match ($exam?->status) {
                        'active' => 'skill-status-active',
                        'pending' => 'skill-status-pending',
                        'expired' => 'skill-status-expired',
                        'suspended' => 'skill-status-suspended',
                        default => 'skill-status-default',
                    };
                @endphp

                <div class="skill-exam-overview mx-auto mb-4">
                    <div class="skill-exam-overview-accent {{ $statusBadgeClass }}"></div>
                    <div class="skill-exam-overview-body">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Exam Details</h5>
                                <small class="text-muted">Review your current exam information</small>
                            </div>
                            <span class="skill-status-badge {{ $statusBadgeClass }}">
                                {{ ucfirst($exam?->status ?? 'N/A') }}
                            </span>
                        </div>
                        <div class="skill-exam-meta-grid">
                            <div class="skill-exam-meta-item">
                                <span class="label">Grade</span>
                                <span class="value">{{ $exam?->Grade?->name ?? 'N/A' }}</span>
                            </div>
                            <div class="skill-exam-meta-item">
                                <span class="label">Level</span>
                                <span class="value">{{ $studentLevelName ?? 'Not set' }}</span>
                            </div>
                            <div class="skill-exam-meta-item">
                                <span class="label">Scope</span>
                                <span class="value">
                                    @if ($isGradeBased)
                                        Grade-based
                                    @else
                                        Section{{ $exam?->Section?->name ? ' (' . $exam->Section->name . ')' : '' }}
                                    @endif
                                </span>
                            </div>
                            <div class="skill-exam-meta-item">
                                <span class="label">Round</span>
                                <span class="value">{{ $exam?->term ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($latestExam && $latestExam->Exam->status == 'pending')
                <div class="skill-exam-alert skill-exam-alert-warning">
                    <i class="fa-solid fa-clock"></i>
                    <div>
                        <strong>Exam is not active yet</strong>
                        <p class="mb-0">The latest exam is pending and will be activated soon. Please check back later.</p>
                    </div>
                </div>
            @elseif ($latestExam && $latestExam->Exam->status == 'active')
                @php
                    $allAttempted = true;
                    $activities = ['reading', 'listening', 'writing', 'speaking', 'sentences_structures'];
                    foreach ($activities as $activity) {
                        // A skill with nothing for this student's level is not theirs to sit.
                        if (count($skillActivities[$activity] ?? []) <= 0) {
                            continue;
                        }
                        $activityStatus = $latestExam->{"{$activity}_status"}['status'] ?? 'unattempted';
                        if ($activityStatus != 'attempted') {
                            $allAttempted = false;
                            break;
                        }
                    }
                @endphp

                @if ($allAttempted)
                    <div class="skill-exam-alert skill-exam-alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <div>
                            <strong>All skills completed!</strong>
                            <p class="mb-0">Congratulations! You have attempted all the active exams.</p>
                        </div>
                    </div>
                @elseif (session('error'))
                    <div class="skill-exam-alert skill-exam-alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Notice</strong>
                            <p class="mb-0">{{ session('error') }}</p>
                        </div>
                    </div>
                @else
                    <div class="skill-exam-alert skill-exam-alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong>Action required</strong>
                            <p class="mb-0">Please attempt all active skill exams below.</p>
                        </div>
                    </div>
                @endif

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    @foreach ($activities as $activity)
                        @php
                            $activityIds = $skillActivities[$activity] ?? [];

                            if (count($activityIds) <= 0) {
                                continue;
                            }

                            $status = $latestExam->{$activity . '_status'}['status'] ?? 'unattempted';
                            $isClickable = $status === 'unattempted';
                            $hasMissingAnswers = false;

                            if ($status === 'attempted' && is_array($activityIds) && count($activityIds)) {
                                $activitiesModels = \App\Models\Activity::with([
                                    'Question.TakeExam' => function ($q) use ($latestExam) {
                                        $q->where('student_id', $latestExam->student_id);
                                    },
                                ])->whereIn('id', $activityIds)->get();

                                foreach ($activitiesModels as $act) {
                                    foreach ($act->Question as $question) {
                                        if (
                                            !isset($question->TakeExam) ||
                                            $question->TakeExam->answer === null ||
                                            $question->TakeExam->answer === '' ||
                                            $question->TakeExam->answer === '{}'
                                        ) {
                                            $hasMissingAnswers = true;
                                            break 2;
                                        }
                                    }
                                }
                            }

                            $statusClass = match ($status) {
                                'attempted' => 'is-attempted',
                                'expired' => 'is-expired',
                                default => 'is-unattempted',
                            };
                        @endphp

                        <div class="col">
                            <div class="student-skill-card {{ $statusClass }} {{ $isClickable ? '' : 'is-locked' }}">
                                <a href="#"
                                    wire:click.prevent="attempt('{{ $activity }}', '{{ $status }}')"
                                    class="student-skill-card-link {{ $isClickable ? '' : 'pe-none' }}">
                                    <div class="student-skill-card-image">
                                        <img src="{{ asset('includes/images/' . $activity . '.jpg') }}"
                                            alt="{{ $activityLabels[$activity] ?? ucfirst(str_replace('_', ' ', $activity)) }}">
                                        <div class="student-skill-card-overlay"></div>
                                        @if ($status === 'attempted')
                                            <span class="student-skill-check"><i class="fa-solid fa-check"></i></span>
                                        @endif
                                    </div>
                                    <div class="student-skill-card-content">
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <h5 class="student-skill-title mb-1">
                                                    {{ $activityLabels[$activity] ?? ucfirst(str_replace('_', ' ', $activity)) }}
                                                </h5>
                                                <p class="student-skill-arabic mb-0">
                                                    {{ $activityTranslations[$activity] ?? '' }}
                                                </p>
                                            </div>
                                            <span class="student-skill-status-pill status-{{ $status }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </div>

                                        @if ($status === 'attempted' && $hasMissingAnswers)
                                            <p class="student-skill-warning mb-2">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                Some questions were left unanswered.
                                            </p>
                                        @endif

                                        <div class="student-skill-action">
                                            @if ($isClickable)
                                                <span>Start Exam <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                            @elseif ($status === 'attempted')
                                                <span>Completed</span>
                                            @else
                                                <span>Not available</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="student-skill-progress">
                                        <div class="student-skill-progress-bar status-{{ $status }}"></div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif (count($expiredExams) > 0)
                <div class="skill-exam-alert skill-exam-alert-danger">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <div>
                        <strong>All exams have expired</strong>
                        <p class="mb-0">The following exams have expired. Please contact your school.</p>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 g-4">
                    @foreach ($expiredExams as $expiredExam)
                        <div class="col">
                            <div class="student-skill-card is-expired is-locked">
                                <div class="student-skill-card-content p-4">
                                    <h5 class="fw-bold mb-3">{{ $expiredExam->Exam->title ?? 'Exam' }}</h5>
                                    <ul class="list-unstyled mb-3 skill-expired-meta">
                                        <li><span>School</span><strong>{{ $expiredExam->Exam->School->name ?? 'N/A' }}</strong></li>
                                        <li><span>Grade</span><strong>{{ $expiredExam->Exam->Grade->name ?? 'N/A' }}</strong></li>
                                        <li><span>Level</span><strong>{{ \App\Support\ExamLevelHelper::levelNamesLabel($expiredExam->Exam->level_ids ?? []) }}</strong></li>
                                        <li><span>Term</span><strong>{{ $expiredExam->Exam->term ?? 'N/A' }}</strong></li>
                                    </ul>
                                    <span class="student-skill-status-pill status-expired">Expired</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="skill-exam-alert skill-exam-alert-info">
                    <i class="fa-solid fa-inbox"></i>
                    <div>
                        <strong>No exams available</strong>
                        <p class="mb-0">There are currently no exams available for you. Please check back later.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <div wire:loading class="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 mb-0 text-muted">Please wait...</p>
                </div>
            </div>
        </div>
    </div>
</div>
