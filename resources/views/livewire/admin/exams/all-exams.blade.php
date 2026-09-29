<div>
    <!-- Search Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label">School</label>
                            <select wire:model.live="school_id" class="form-select bg-light border-light rounded">
                                <option value="">All Schools</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label">Grade</label>
                            <select wire:model.live="grade_id" class="form-select bg-light border-light rounded">
                                <option value="">All Grades</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label">Level</label>
                            <select wire:model="level_id" class="form-select bg-light border-light rounded">
                                <option value="">All Levels</option>
                                @foreach ($levels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label">Exam Status</label>
                            <select wire:model="status" class="form-select bg-light border-light rounded">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label">Archive</label>
                            <select wire:model="archiveStatus" class="form-select bg-light border-light rounded">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-9">
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="manageSearch" class="btn btn-primary flex-fill"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="manageSearch">
                                        <i class="bx bx-search-alt"></i> Search
                                    </span>
                                    <span wire:loading wire:target="manageSearch">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Searching...
                                    </span>
                                </button>
                                <button type="button" wire:click="resetFilters" class="btn btn-secondary flex-fill"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="resetFilters">
                                        <i class="bx bx-reset"></i> Reset
                                    </span>
                                    <span wire:loading wire:target="resetFilters">
                                        <span class="spinner-border spinner-border-sm" role="status"
                                            aria-hidden="true"></span>
                                        Resetting...
                                    </span>
                                </button>

                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                    data-bs-target="#staticBackdrop">
                                    Refresh Exam Activities
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.list-toolbar>
        <button type="button" wire:click="resyncStudents" class="btn btn-warning" wire:loading.attr="disabled"
            wire:target="resyncStudents">
            <span wire:loading.remove wire:target="resyncStudents">
                <i class="bx bx-sync"></i> Resync Students
            </span>
            <span wire:loading wire:target="resyncStudents">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Syncing...
            </span>
        </button>
        <button type="button" wire:click="openBulkStatusModal" class="btn btn-info" wire:loading.attr="disabled"
            wire:target="openBulkStatusModal">
            <span wire:loading.remove wire:target="openBulkStatusModal">
                <i class="bx bx-edit-alt"></i> Bulk Update Status
            </span>
            <span wire:loading wire:target="openBulkStatusModal">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading...
            </span>
        </button>
        @if (getPermissions('exams', 'delete'))
            <button type="button" class="btn btn-danger" wire:click.prevent="bulkArchiveExams" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="bulkArchiveExams,bulkArchiveExamsConfirm">
                    <i class='bx bx-archive'></i> Bulk Archive
                </span>
                <span wire:loading wire:target="bulkArchiveExams,bulkArchiveExamsConfirm">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Archiving...
                </span>
            </button>
            @if ($archiveStatus !== 'active')
                <button type="button" class="btn btn-secondary" wire:click.prevent="bulkRestoreExams"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="bulkRestoreExams,bulkRestoreExamsConfirm">
                        <i class='bx bx-undo'></i> Bulk Restore
                    </span>
                    <span wire:loading wire:target="bulkRestoreExams,bulkRestoreExamsConfirm">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Restoring...
                    </span>
                </button>
            @endif
        @endif
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkCreateExamModal">
            <i class="bx bx-layer-plus"></i> Bulk Create Exam
        </button>
        <a href="{{ route('admin.add-exam') }}" class="btn btn-primary">
            <i class="bx bx-plus"></i> Add Exam
        </a>
    </x-admin.list-toolbar>

    @php
        $activitySkills = [
            'reading' => ['label' => 'Reading', 'route' => 'admin.exam-reading-activities', 'badge' => 'badge-outline-primary'],
            'listening' => ['label' => 'Listening', 'route' => 'admin.exam-listening-activities', 'badge' => 'badge-outline-success'],
            'writing' => ['label' => 'Writing', 'route' => 'admin.exam-writing-activities', 'badge' => 'badge-outline-warning'],
            'speaking' => ['label' => 'Speaking', 'route' => 'admin.exam-speaking-activities', 'badge' => 'badge-outline-danger'],
            'sentences_structures' => ['label' => 'Sentences', 'route' => 'admin.exam-sentences-structures-activities', 'badge' => 'badge-outline-info'],
        ];
    @endphp

    <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-3 g-3 mb-3">
        @forelse ($exams as $exam)
            <div class="col">
                <div class="card admin-exam-card h-100">
                    <div class="exam-card-header">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h6 class="mb-1 fw-semibold">{{ $exam->School->name ?? '—' }}</h6>
                                <small class="text-muted">
                                    {{ $exam->Grade?->name ?? '—' }}
                                    · {{ \App\Support\ExamLevelHelper::levelNamesLabel($exam->level_ids) }}
                                    · {{ $exam->term ?? '—' }}
                                </small>
                                @if ($exam->trashed())
                                    <span class="badge bg-secondary mt-1">Archived</span>
                                @endif
                            </div>
                            <div style="min-width: 130px;">
                                @if (!$exam->trashed())
                                    <select class="form-select form-select-sm"
                                        wire:change.prevent="changeStatus($event.target.value, {{ $exam->id }})">
                                        <option {{ $exam->status == 'pending' ? 'selected' : '' }} value="pending">Pending</option>
                                        <option {{ $exam->status == 'active' ? 'selected' : '' }} value="active">Active</option>
                                        <option {{ $exam->status == 'suspended' ? 'selected' : '' }} value="suspended">Suspended</option>
                                        <option {{ $exam->status == 'expired' ? 'selected' : '' }} value="expired">Expired</option>
                                    </select>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($exam->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Created: {{ date('d M, Y H:i', strtotime($exam->created_at)) }}
                            · Updated: {{ date('d M, Y H:i', strtotime($exam->updated_at)) }}
                        </small>
                    </div>

                    <div class="exam-card-body">
                        @php
                            // On a multi-level exam the totals below are every level added
                            // together, which is not the paper any one student sits.
                            $coverage = \App\Support\ExamActivityQuery::levelCoverage($exam, array_keys($activitySkills));
                            $gaps = collect($coverage)->filter(fn ($row) => $row['missing'] !== []);
                        @endphp

                        <small class="text-muted d-block mb-2 fw-semibold">
                            Activities
                            @if ($coverage)
                                <span class="fw-normal">· totals across {{ count($coverage) }} levels</span>
                            @endif
                        </small>
                        <div class="activity-badges">
                            @foreach ($activitySkills as $key => $skill)
                                @php
                                    $count = count($exam->{$key . '_activities'} ?? []);
                                    $perLevel = collect($coverage)
                                        ->map(fn ($row) => $row['level'] . ': ' . $row['counts'][$key])
                                        ->implode(' · ');
                                @endphp
                                <a href="{{ route($skill['route'], ['exam_id' => $exam->id]) }}">
                                    <span class="badge {{ $skill['badge'] }}" @if ($perLevel) title="{{ $perLevel }}" @endif>
                                        {{ $skill['label'] }}: {{ $count > 0 ? $count : 'Not Assigned' }}
                                    </span>
                                </a>
                            @endforeach
                        </div>

                        @if ($gaps->isNotEmpty())
                            {{-- A level with no activities for a skill means those students
                                 have nothing to sit for it, however large the total looks. --}}
                            <div class="alert alert-warning py-2 px-3 mt-2 mb-0 small">
                                <i class="bx bx-error-circle align-middle"></i>
                                @foreach ($gaps as $row)
                                    <div>
                                        <strong>{{ $row['level'] }}</strong> has no
                                        {{ collect($row['missing'])->map(fn ($m) => $activitySkills[$m]['label'])->join(', ', ' or ') }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="exam-card-footer">
                        <a href="{{ route('admin.exam-preview-types', ['exam' => $exam->id]) }}"
                            class="btn btn-sm btn-success" title="Preview Exam" target="_blank" rel="noopener noreferrer">
                            <i class="bx bx-show"></i> Preview
                        </a>
                        @if (!$exam->trashed() && getPermissions('exams', 'edit'))
                            <a href="{{ route('admin.edit-exam', ['exam' => $exam->id]) }}" class="btn btn-sm btn-primary">
                                <i class="bx bx-edit"></i> Edit
                            </a>
                        @endif
                        @if (!$exam->trashed() && getPermissions('exams', 'delete'))
                            <button type="button" class="btn btn-sm btn-danger"
                                wire:click.prevent="archiveExam({{ $exam->id }})">
                                <i class="bx bx-archive"></i> Archive
                            </button>
                        @endif
                        @if ($exam->trashed() && getPermissions('exams', 'delete'))
                            <button type="button" class="btn btn-sm btn-secondary"
                                wire:click.prevent="restoreExam({{ $exam->id }})">
                                <i class="bx bx-undo"></i> Restore
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <img src="{{ asset('assets/images/empty.png') }}" alt="Empty List Image" width="25%">
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="float-end">
                {{ $exams->links() }}
            </div>
        </div>
    </div>

    <!-- Bulk Status Modal -->
    <div wire:ignore.self class="modal fade" id="bulkStatusModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="bulkStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkStatusModalLabel">Bulk Update Exam Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">School</label>
                            <select wire:model.live="bulkSchoolId" class="form-select bg-light border-light rounded">
                                <option value="">Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                            @error('bulkSchoolId')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Term</label>
                            <select wire:model.live="bulkTerm" class="form-select bg-light border-light rounded">
                                <option value="">All Terms</option>
                                @foreach ($terms as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2">
                                @if (!empty($bulkStatusCounts))
                                    <div class="float-start">
                                        @foreach ($bulkStatusCounts as $status => $count)
                                            <span class="badge p-2 bg-secondary">{{ ucfirst($status) }}:
                                                {{ $count }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="ms-auto">
                                    <strong>Total Exams: </strong> {{ $bulkInScopeCount }}
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Change From</label>
                            <select wire:model.live="bulkFromStatus"
                                class="form-select bg-light border-light rounded">
                                <option value="">Select Current Status</option>
                                <option value="pending" @if ($bulkToStatus === 'pending') disabled @endif>Pending
                                </option>
                                <option value="active" @if ($bulkToStatus === 'active') disabled @endif>Active
                                </option>
                                <option value="suspended" @if ($bulkToStatus === 'suspended') disabled @endif>Suspended
                                </option>
                                <option value="expired" @if ($bulkToStatus === 'expired') disabled @endif>Expired
                                </option>
                            </select>
                            @error('bulkFromStatus')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Change To</label>
                            <select wire:model.live="bulkToStatus" class="form-select bg-light border-light rounded">
                                <option value="">Select New Status</option>
                                <option value="pending" @if ($bulkFromStatus === 'pending') disabled @endif>Pending
                                </option>
                                <option value="active" @if ($bulkFromStatus === 'active') disabled @endif>Active
                                </option>
                                <option value="suspended" @if ($bulkFromStatus === 'suspended') disabled @endif>Suspended
                                </option>
                                <option value="expired" @if ($bulkFromStatus === 'expired') disabled @endif>Expired
                                </option>
                            </select>
                            @error('bulkToStatus')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" wire:click="bulkUpdateStatus" class="btn btn-primary"
                        wire:loading.attr="disabled" wire:target="bulkUpdateStatus">
                        <span wire:loading.remove wire:target="bulkUpdateStatus">Update Status</span>
                        <span wire:loading wire:target="bulkUpdateStatus">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('open-bulk-status-modal', function() {
            var myModal = new bootstrap.Modal(document.getElementById('bulkStatusModal'));
            myModal.show();
        });

        window.addEventListener('close-bulk-status-modal', function() {
            var modalEl = document.getElementById('bulkStatusModal');
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });
    </script>

    <!-- Modal -->
    <div wire:ignore.self class="modal" id="imageModal" tabindex="-1" role="dialog"
        aria-labelledby="imageModalLabel" aria-hidden="true">
        <button type="button" class="btn text-white position-absolute m-3 fs-2" data-bs-dismiss="modal"
            aria-label="Close" style="top: 20px; right:30px;"><i class='bx bx-x-circle'></i></button>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border: none; background: none;">
                <div class="modal-body p-0">
                    <!-- Image goes here -->
                    <img src="" alt="Image" id="modalImage" width="400px">
                </div>
            </div>
            <!-- Close button hidden on small screens -->
        </div>
    </div>

    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                {{-- Header --}}
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">
                        Select And Refresh Activities for School
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="refreshActiviteis">
                    <div class="modal-body">
                        <div class="row g-3">

                            {{-- School --}}
                            <div class="col-12">
                                <label class="form-label fw-semibold">School</label>

                                <select wire:model="inputs.school_id"
                                    class="form-select bg-light border-light rounded">
                                    <option value="">All Schools</option>
                                    @foreach ($schools as $school)
                                        <option value="{{ $school->id }}">
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('inputs.school_id')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Activities --}}
                            @foreach ([
        'reading' => 'Reading',
        'listening' => 'Listening',
        'writing' => 'Writing',
        'speaking' => 'Speaking',
        'sentences_structures' => 'Sentence Structures',
    ] as $activity => $label)
                                <div class="col-md-6">
                                    <div class="card">
                                        <label
                                            class="card-body h-100 p-2 d-flex align-items-center gap-2 cursor-pointer"
                                            for="activity_{{ $activity }}">
                                            <input type="checkbox" class="form-check-input m-0"
                                                id="activity_{{ $activity }}" value="{{ $activity }}"
                                                wire:model="inputs.activities">

                                            <span class="fw-semibold">{{ $label }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach


                            {{-- Activities error --}}
                            <div class="col-12">
                                @error('inputs.activities')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Refresh Activities
                        </button>
                    </div>
                </form>


            </div>
        </div>
    </div>

    {{-- Bulk Create Exam (separate Livewire component renders its own modal) --}}
    <livewire:admin.exams.bulk-create-exam />

</div>
