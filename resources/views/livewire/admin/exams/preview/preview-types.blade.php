<div class="student-skill-exams">
    <x-preview-header
        :title="'Exam Preview'"
        :exam="$exam"
        :backUrl="route('admin.exams')"
        backLabel="Back to Exams"
    />

    <section class="student-info mt-4">
        <div class="container">
            <div class="skill-exams-intro text-center mb-4">
                <h4 class="fw-bold mb-2">Student View Preview</h4>
                <p class="text-muted mx-auto mb-0" style="max-width: 560px;">
                    This is how the exam will appear to students. Select a skill below to preview its content.
                </p>
            </div>

            @php
                $isGradeBased = empty($exam->section_id);
                $statusBadgeClass = match ($exam->status) {
                    'active' => 'skill-status-active',
                    'pending' => 'skill-status-pending',
                    'expired' => 'skill-status-expired',
                    'suspended' => 'skill-status-suspended',
                    default => 'skill-status-default',
                };
                $activities = ['reading', 'listening', 'writing', 'speaking', 'sentences_structures'];
                $hasActivities = false;
                foreach ($activities as $activity) {
                    if (count($activityIdsByType[$activity] ?? []) > 0) {
                        $hasActivities = true;
                        break;
                    }
                }
            @endphp

            <div class="skill-exam-overview mx-auto mb-4">
                <div class="skill-exam-overview-accent {{ $statusBadgeClass }}"></div>
                <div class="skill-exam-overview-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h5 class="fw-bold mb-1">Exam Details</h5>
                            <small class="text-muted">Same information students see on their exams page</small>
                        </div>
                        <span class="skill-status-badge {{ $statusBadgeClass }}">
                            {{ ucfirst($exam->status ?? 'N/A') }}
                        </span>
                    </div>
                    <div class="skill-exam-meta-grid">
                        <div class="skill-exam-meta-item">
                            <span class="label">School</span>
                            <span class="value">{{ $exam->School->name ?? 'N/A' }}</span>
                        </div>
                        <div class="skill-exam-meta-item">
                            <span class="label">Grade</span>
                            <span class="value">{{ $exam->Grade->name ?? 'N/A' }}</span>
                        </div>
                        <div class="skill-exam-meta-item">
                            <span class="label">Level</span>
                            <span class="value">{{ \App\Support\ExamLevelHelper::levelNamesLabel($exam->level_ids ?? []) }}</span>
                        </div>
                        <div class="skill-exam-meta-item">
                            <span class="label">Scope</span>
                            <span class="value">
                                @if ($isGradeBased)
                                    Grade-based
                                @else
                                    Section{{ $exam->Section?->name ? ' (' . $exam->Section->name . ')' : '' }}
                                @endif
                            </span>
                        </div>
                        <div class="skill-exam-meta-item">
                            <span class="label">Round</span>
                            <span class="value">{{ $exam->term ?? 'N/A' }}</span>
                        </div>
                        <div class="skill-exam-meta-item">
                            <span class="label">Mode</span>
                            <span class="value">Admin Preview</span>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('error'))
                <div class="skill-exam-alert skill-exam-alert-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div>
                        <strong>Notice</strong>
                        <p class="mb-0">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if ($levels->isNotEmpty())
                {{-- This exam covers several levels. Each is a separate paper, so
                     preview them one at a time, exactly as a student sits them. --}}
                <div class="skill-exam-overview mx-auto mb-4">
                    <div class="skill-exam-overview-body">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div>
                                <h5 class="fw-bold mb-1">Preview level</h5>
                                <small class="text-muted">
                                    This exam covers {{ $levels->count() }} levels. A student sits only their own.
                                </small>
                            </div>
                            <div class="ms-auto d-flex flex-wrap gap-2">
                                @foreach ($levels as $level)
                                    <button type="button" wire:click="$set('previewLevelId', '{{ $level->id }}')"
                                        class="btn btn-sm {{ (string) $previewLevelId === (string) $level->id ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ $level->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasActivities)
                <div class="skill-exam-alert skill-exam-alert-info">
                    <i class="fa-solid fa-eye"></i>
                    <div>
                        <strong>Preview mode</strong>
                        <p class="mb-0">Click any skill card below to open that section exactly as a student would see it.</p>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    @foreach ($activities as $activity)
                        @php
                            $activityIds = $activityIdsByType[$activity] ?? [];
                            if (count($activityIds) <= 0) {
                                continue;
                            }
                            $activityCount = count($activityIds);
                        @endphp

                        <div class="col">
                            <div class="student-skill-card is-preview">
                                <a href="{{ route('admin.exam-preview-type', ['exam' => $exam->id, 'type' => $activity, 'level' => $previewLevelId ?: null]) }}"
                                    class="student-skill-card-link">
                                    <div class="student-skill-card-image">
                                        <img src="{{ asset('includes/images/' . $activity . '.jpg') }}"
                                            alt="{{ $activityLabels[$activity] ?? ucfirst(str_replace('_', ' ', $activity)) }}">
                                        <div class="student-skill-card-overlay"></div>
                                        <span class="student-skill-preview-icon"><i class="fa-solid fa-eye"></i></span>
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
                                            <span class="student-skill-status-pill status-preview">
                                                {{ $activityCount }} {{ Str::plural('Activity', $activityCount) }}
                                            </span>
                                        </div>

                                        <div class="student-skill-action">
                                            Preview Section <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </div>
                                    </div>
                                    <div class="student-skill-progress">
                                        <div class="student-skill-progress-bar status-preview"></div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="skill-exam-alert skill-exam-alert-info">
                    <i class="fa-solid fa-inbox"></i>
                    <div>
                        @if ($levels->isNotEmpty())
                            {{-- The exam may well have activities, just none for this level. --}}
                            <strong>Nothing assigned for this level</strong>
                            <p class="mb-0">
                                No activities are assigned to
                                {{ $levels->firstWhere('id', (int) $previewLevelId)?->name ?? 'this level' }}
                                yet, so a student on it would have nothing to sit. Assign activities for this
                                level, or pick another level above.
                            </p>
                        @else
                            <strong>No activities assigned</strong>
                            <p class="mb-0">This exam has no activities assigned yet. Please assign activities before previewing.</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
