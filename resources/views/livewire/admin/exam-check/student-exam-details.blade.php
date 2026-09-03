<div>
    @php
        $statusBadges = [
            'attempted' => 'badge bg-success',
            'unattempted' => 'badge bg-warning text-dark',
            'expired' => 'badge bg-danger',
        ];
        $skillLabels = [
            'reading' => 'Reading',
            'listening' => 'Listening',
            'writing' => 'Writing',
            'speaking' => 'Speaking',
            'sentences_structures' => 'Sentences Structures',
        ];
    @endphp

    <x-admin.list-toolbar>
        <button wire:click="markChecked" class="btn btn-primary">
            <i class="bx bx-check-circle"></i> Mark As Checked
        </button>
    </x-admin.list-toolbar>

    <div class="admin-overview-card">
        <h6 class="overview-title">Student Details</h6>
        <div class="admin-meta-grid">
            <div class="meta-item">
                <p class="meta-label">Name</p>
                <p class="meta-value">{{ $student_exam->Student->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Registration</p>
                <p class="meta-value">{{ $student_exam->Student->registration ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Username</p>
                <p class="meta-value">{{ $student_exam->Student->user_name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">School</p>
                <p class="meta-value">{{ $student_exam->Student->School->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Grade</p>
                <p class="meta-value">{{ $student_exam->Student->Grade->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Section</p>
                <p class="meta-value">{{ $student_exam->Student->Section->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Assigned Level</p>
                <p class="meta-value">{{ $student_exam->Student->assignedLevel->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Nationality</p>
                <p class="meta-value">{{ $student_exam->Student->nationality ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Category</p>
                <p class="meta-value">{{ $student_exam->Student->category ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Academic Year</p>
                <p class="meta-value">{{ $student_exam->Student->year ?? '—' }}</p>
            </div>
        </div>
    </div>

    <div class="admin-overview-card">
        <h6 class="overview-title">Exam Overview</h6>
        <div class="admin-meta-grid">
            <div class="meta-item">
                <p class="meta-label">School</p>
                <p class="meta-value">{{ $student_exam->Exam->School->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Grade</p>
                <p class="meta-value">{{ $student_exam->Exam->Grade->name ?? '—' }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Level</p>
                <p class="meta-value">{{ \App\Support\ExamLevelHelper::levelNamesLabel($student_exam->Exam->level_ids ?? []) }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Term</p>
                <p class="meta-value">{{ ucwords($student_exam->Exam->term ?? '') }}</p>
            </div>
            <div class="meta-item">
                <p class="meta-label">Exam Status</p>
                <p class="meta-value">{{ ucfirst($student_exam->Exam->status ?? '') }}</p>
            </div>
        </div>
    </div>

    @foreach ($status_array as $skillKey => $statusField)
        @php
            $st = $student_exam->{$statusField} ?? [];
            $status = $st['status'] ?? 'unattempted';
            $marks = collect(optional($student_exam->Result)->{$skillKey . '_marks'})->sum();
            $submittedAt = $this->getSkillSubmittedAt($skillKey, $st);
            $startedAt = $this->formatSkillDate($st['start_time'] ?? null);
            $windowEndsAt = $this->formatSkillDate($st['end_time'] ?? null);
            $deviceIp = $this->getSkillDeviceIp($st);
        @endphp
        <div class="admin-skill-card">
            <div class="skill-card-header">
                <h6 class="mb-0 fw-semibold">{{ $skillLabels[$skillKey] ?? ucfirst($skillKey) }}</h6>
                <span class="{{ $statusBadges[$status] ?? 'badge bg-secondary' }}">{{ ucfirst($status) }}</span>
            </div>
            <div class="skill-card-body">
                <div class="row g-2">
                    @if ($startedAt)
                        <div class="col-sm-6 col-md-4">
                            <small class="text-muted d-block">Started</small>
                            <span>{{ $startedAt }}</span>
                        </div>
                    @endif
                    @if ($submittedAt && $status === 'attempted')
                        <div class="col-sm-6 col-md-4">
                            <small class="text-muted d-block">Submitted</small>
                            <span>{{ $submittedAt }}</span>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <small class="text-muted d-block">Device IP</small>
                            <span>{{ $deviceIp ?? '—' }}</span>
                        </div>
                    @endif
                    @if ($windowEndsAt && $status === 'expired' && ! $submittedAt)
                        <div class="col-sm-6 col-md-4">
                            <small class="text-muted d-block">Window Ended</small>
                            <span>{{ $windowEndsAt }}</span>
                        </div>
                    @endif
                    @if ($marks)
                        <div class="col-sm-6 col-md-4">
                            <small class="text-muted d-block">Marks</small>
                            <span class="fw-semibold text-primary">{{ $marks }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="skill-card-footer">
                @if ($status === 'attempted')
                    <a href="{{ route('admin.check-exam', ['type' => $skillKey, 'exam_id' => $student_exam->id]) }}"
                        class="btn btn-sm btn-secondary">
                        <i class="bx bx-edit"></i> Check Activity
                    </a>
                @endif
                @if (! $student_exam->checked && in_array($status, ['expired', 'attempted']))
                    <button type="button" class="btn btn-sm btn-primary" wire:click="confirmReassign('{{ $statusField }}')">
                        <i class="bx bx-refresh"></i> Re-assign Activity
                    </button>
                @endif
            </div>
        </div>
    @endforeach
</div>
