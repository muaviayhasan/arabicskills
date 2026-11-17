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

                @php
                    /**
                     * How each skill presents itself: its colour, its icon and
                     * its picture, kept together so a skill is one entry here
                     * rather than rules scattered through the stylesheet.
                     *
                     * The colours are not the obvious ones. White on the
                     * natural green and teal of the reference measures 4.10:1
                     * and 3.51:1, under the 4.5:1 that button text needs, so
                     * each is the nearest shade that passes.
                     *
                     * The photographs come from the AST Arabic site, which
                     * already carried this design: five already cropped and
                     * compressed, 89KB for all of them against the 994KB of
                     * illustrations they replace. Their width and height are
                     * recorded so the card can take each photograph's own
                     * proportions and crop nothing, and so nothing shifts
                     * while they load.
                     *
                     * A skill listed without an 'art' falls back to its colour
                     * and a large icon, which reads as a decision rather than
                     * a missing file.
                     */
                    $skillArt = [
                        'reading' => ['colour' => '#0b63ce', 'deep' => '#0848a0', 'icon' => 'fa-book-open', 'art' => 'reading.jpg', 'w' => 338, 'h' => 222],
                        'listening' => ['colour' => '#6b21a8', 'deep' => '#4c1580', 'icon' => 'fa-headphones', 'art' => 'listening.jpg', 'w' => 336, 'h' => 222],
                        'writing' => ['colour' => '#3f7a16', 'deep' => '#2f5e0f', 'icon' => 'fa-pencil', 'art' => 'writing.jpg', 'w' => 336, 'h' => 222],
                        'speaking' => ['colour' => '#0b7f99', 'deep' => '#086478', 'icon' => 'fa-comments', 'art' => 'speaking.jpg', 'w' => 528, 'h' => 210],
                        'sentences_structures' => ['colour' => '#c2185b', 'deep' => '#9c1149', 'icon' => 'fa-sitemap', 'art' => 'sentences_structures.jpg', 'w' => 558, 'h' => 210],
                    ];
                @endphp

                @php
                    // The layout depends on how many cards there are, so it has
                    // to be known before the loop: a skill with nothing for this
                    // student's level is skipped below.
                    $shownSkills = array_values(array_filter(
                        $activities,
                        fn ($skill) => count($skillActivities[$skill] ?? []) > 0
                    ));
                @endphp

                <div class="ast-skills ast-skills--{{ count($shownSkills) }}">
                    @foreach ($activities as $activity)
                        @php
                            $activityIds = $skillActivities[$activity] ?? [];

                            if (count($activityIds) <= 0) {
                                continue;
                            }

                            $status = $latestExam->{$activity . '_status'}['status'] ?? 'unattempted';
                            $isClickable = $status === 'unattempted';
                            // Worked out for every skill at once in the component.
                            $hasMissingAnswers = $missingAnswers[$activity] ?? false;

                            $art = $skillArt[$activity] ?? ['colour' => '#0b63ce', 'deep' => '#0848a0', 'icon' => 'fa-book-open', 'art' => null, 'w' => 338, 'h' => 222];
                            $english = $activityLabels[$activity] ?? ucfirst(str_replace('_', ' ', $activity));
                            $arabic = $activityTranslations[$activity] ?? '';

                            $statusClass = match ($status) {
                                'attempted' => 'is-attempted',
                                'expired' => 'is-expired',
                                default => 'is-unattempted',
                            };

                            $statusLabel = match ($status) {
                                'attempted' => 'Completed',
                                'expired' => 'Expired',
                                default => 'Not started',
                            };

                            $statusIcon = match ($status) {
                                'attempted' => 'fa-circle-check',
                                'expired' => 'fa-circle-xmark',
                                default => 'fa-circle-dot',
                            };
                        @endphp

                        <div class="ast-skill {{ $statusClass }} {{ $isClickable ? '' : 'is-locked' }}"
                            style="--skill: {{ $art['colour'] }}; --skill-deep: {{ $art['deep'] }}; --art-ratio: {{ $art['w'] }} / {{ $art['h'] }};">
                            <a href="#" wire:click.prevent="attempt('{{ $activity }}', '{{ $status }}')"
                                class="ast-skill__link {{ $isClickable ? '' : 'pe-none' }}"
                                @if (!$isClickable) tabindex="-1" aria-disabled="true" @endif
                                aria-label="{{ $english }} — {{ $statusLabel }}">

                                <div class="ast-skill__art {{ $art['art'] ? '' : 'ast-skill__art--plain' }}">
                                    @if ($art['art'])
                                        <img src="{{ versioned_asset('includes/images/skills/' . $art['art']) }}"
                                            alt="" width="{{ $art['w'] }}" height="{{ $art['h'] }}"
                                            loading="lazy" decoding="async">
                                    @else
                                        <i class="fa-solid {{ $art['icon'] }}" aria-hidden="true"></i>
                                    @endif

                                    {{-- The colour sweeping in from the top, and the body curving up
                                         into the picture: between them they make the shape. --}}
                                    <span class="ast-skill__band" aria-hidden="true">
                                        <svg viewBox="0 0 100 100" preserveAspectRatio="none">
                                            <defs>
                                                <linearGradient id="sweep-{{ $activity }}" x1="0" y1="1" x2="1" y2="0">
                                                    <stop offset="0" stop-color="var(--skill)" stop-opacity=".15" />
                                                    <stop offset=".55" stop-color="var(--skill)" stop-opacity=".9" />
                                                    <stop offset="1" stop-color="var(--skill-deep)" />
                                                </linearGradient>
                                            </defs>
                                            <path fill="var(--skill)" opacity=".3"
                                                d="M100 0H70C80 26 80 56 66 80 60 90 52 96 44 100H100Z" />
                                            <path fill="url(#sweep-{{ $activity }})"
                                                d="M100 0H79C88 26 87 56 75 79 69 89 62 95 55 100H100Z" />
                                        </svg>
                                    </span>

                                    <span class="ast-skill__wave" aria-hidden="true">
                                        <svg viewBox="0 0 400 56" preserveAspectRatio="none">
                                            <path fill="var(--skill-deep)"
                                                d="M0 38C120 52 250 30 400 2V12C250 38 120 56 0 44Z" />
                                            <path fill="#fff" d="M0 56V44C120 56 250 38 400 12V56Z" />
                                        </svg>
                                    </span>

                                    <span class="ast-skill__badge" aria-hidden="true">
                                        <i class="fa-solid {{ $art['icon'] }}"></i>
                                    </span>

                                    {{-- Paired with a word and an icon, never colour alone. --}}
                                    <span class="ast-skill__status">
                                        <i class="fa-solid {{ $statusIcon }}" aria-hidden="true"></i>
                                        {{ $statusLabel }}
                                    </span>

                                    @if ($status === 'attempted')
                                        <span class="ast-skill__done" aria-hidden="true">
                                            <i class="fa-solid fa-circle-check"></i>
                                        </span>
                                    @endif
                                </div>

                                <div class="ast-skill__body">
                                    <h3 class="ast-skill__ar" lang="ar" dir="rtl">{{ $arabic }}</h3>
                                    <p class="ast-skill__en">{{ $english }}</p>
                                    <span class="ast-skill__rule" aria-hidden="true"></span>

                                    @if ($status === 'attempted' && $hasMissingAnswers)
                                        <p class="ast-skill__note">
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                            Some questions were left unanswered.
                                        </p>
                                    @endif

                                    <span class="ast-skill__cta">
                                        @if ($isClickable)
                                            <span class="ast-skill__cta-ar" lang="ar" dir="rtl">ابدأ الاختبار</span>
                                            <span class="ast-skill__cta-en">Start Exam &rarr;</span>
                                        @elseif ($status === 'attempted')
                                            <span class="ast-skill__cta-ar" lang="ar" dir="rtl">تم الإنجاز</span>
                                            <span class="ast-skill__cta-en">Completed</span>
                                        @else
                                            <span class="ast-skill__cta-ar" lang="ar" dir="rtl">غير متاح</span>
                                            <span class="ast-skill__cta-en">Not available</span>
                                        @endif
                                    </span>
                                </div>
                            </a>
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
