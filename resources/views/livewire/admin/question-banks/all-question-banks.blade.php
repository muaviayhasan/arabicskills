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
                            <label class="form-label">Year / Grade</label>
                            <select wire:model="grade_id" class="form-select bg-light border-light rounded">
                                <option value="">All Grades</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label">Level</label>
                            <select wire:model="level_id" class="form-select bg-light border-light rounded">
                                <option value="">All Levels</option>
                                @foreach ($levels as $lvl)
                                    <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label">Round</label>
                            <select wire:model="term" class="form-select bg-light border-light rounded">
                                <option value="">All Rounds</option>
                                @foreach ($terms as $termOption)
                                    <option value="{{ $termOption }}">{{ $termOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-2">
                            <label class="form-label">Skill</label>
                            <select wire:model="type" class="form-select bg-light border-light rounded">
                                <option value="">All Skills</option>
                                @foreach ($types as $t)
                                    <option value="{{ $t }}">{{ Str::title(Str::replace('_', ' ', $t)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-8 col-lg-8">
                            <label class="form-label">Assessment Title</label>
                            <input type="text" wire:model="search" class="form-control bg-light border-light rounded"
                                placeholder="Search by assessment title or activity text">
                        </div>
                        <div class="col-12 col-md-4 col-lg-4">
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.list-toolbar>
        <button type="button" class="btn btn-outline-primary" wire:click.prevent="openDuplicateModal()"
            wire:loading.attr="disabled" wire:target="openDuplicateModal">
            <span wire:loading.remove wire:target="openDuplicateModal">
                <i class="bx bx-copy"></i> Copy Questions to Another Term
            </span>
            <span wire:loading wire:target="openDuplicateModal">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Opening...
            </span>
        </button>
    </x-admin.list-toolbar>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-centered align-middle table-nowrap table-check"
                            style="margin-bottom: 70px; !important">
                            <thead>
                                <tr class="fw-semibold text-center">
                                    <th style="width: 90px;">
                                        Image
                                    </th>
                                    <th>Type</th>
                                    <th>Grade</th>
                                    <th>Level</th>
                                    <th>Assessment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($activities as $activity)
                                    <tr class="text-center">
                                        <td>
                                            <button type="button" class="imgButtons btn p-0  border-0 shadow-none"
                                                data-bs-toggle="modal" data-bs-target="#imageModal"
                                                data-image="{{ read_image($activity->image) }}">

                                                <div class="rounded border-0 table-small-img">
                                                    <img src="{{ read_image($activity->image) }}" class="rounded"
                                                        alt="image" width="100%" height="100%">
                                                </div>

                                            </button>
                                        </td>
                                        <td class="fw-semibold">
                                            {{ Str::title(Str::replace('_', ' ', $activity->type)) }}
                                            <br />
                                            <small class="text-muted">
                                                Created: {{ date('d M, Y H:i', strtotime($activity->created_at)) }} <br />
                                                Updated: {{ date('d M, Y H:i', strtotime($activity->updated_at)) }}
                                            </small>
                                        </td>
                                        <td>
                                            {{ $activity->Grade?->name ?? 'N/A' }}
                                            <div>{{ $activity->term ?? '' }}</div>
                                        </td>
                                        <td>
                                            {{ $activity->assignedLevel?->name ?? 'N/A' }}
                                        </td>

                                        <td>
                                            {{ $activity->title }}
                                        </td>

                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-danger light sharp"
                                                    data-bs-toggle="dropdown">
                                                    <svg width="20px" height="20px" viewBox="0 0 24 24" version="1.1">
                                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                            <rect x="0" y="0" width="24" height="24" />
                                                            <circle fill="#000000" cx="5" cy="12" r="2" />
                                                            <circle fill="#000000" cx="12" cy="12" r="2" />
                                                            <circle fill="#000000" cx="19" cy="12" r="2" />
                                                        </g>
                                                    </svg>
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if (getPermissions('question_banks', 'edit'))
                                                        <a href="{{ route('admin.edit-question-bank', ['activity_id' => $activity->id]) }}"
                                                            class="dropdown-item text-center">Edit</a>
                                                    @endif
                                                    @if (getPermissions('question_banks', 'delete'))
                                                        <a class="dropdown-item text-center"
                                                            wire:click.prevent="deleteActivity({{ $activity->id }})"
                                                            href="#">Delete</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td class="bg-white" colspan="6">

                                            <img src="{{ asset('assets/images/empty.png') }}" alt="Empty List Image"
                                                width="25%" height="100px">
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <div class="float-end">
                            {{ $activities->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <!-- Duplicate Term Modal -->
    <div wire:ignore.self class="modal fade" id="duplicateTermModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Copy Questions to Another Term</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if(!empty($duplicateErrors))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($duplicateErrors as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-2">
                        <label class="form-label">Source Term</label>
                        <select wire:model.live="duplicateSourceTerm" class="form-select"
                            wire:loading.attr="disabled" wire:target="duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm">
                            <option value="">Select source term</option>
                            @foreach($terms as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Target Term</label>
                        <select wire:model.live="duplicateTargetTerm" class="form-select"
                            wire:loading.attr="disabled" wire:target="duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm"
                            @disabled(!$duplicateSourceTerm)>
                            <option value="">Select target term</option>
                            @foreach($terms as $t)
                                @if($t !== $duplicateSourceTerm)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endif
                            @endforeach
                        </select>
                        @if(!$duplicateSourceTerm)
                            <small class="text-muted">Choose a source term first.</small>
                        @endif
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Types</label>
                        <div>
                            @foreach($types as $t)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" value="{{ $t }}" id="dup-type-{{ $t }}" wire:model.live="selectedDuplicateTypes"
                                        wire:loading.attr="disabled" wire:target="duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm">
                                    <label class="form-check-label" for="dup-type-{{ $t }}">{{ Str::title(Str::replace('_',' ',$t)) }}</label>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Leave all unchecked to include every type from the source term.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">When matching activity already exists in target</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="duplicate-mode-skip" value="skip"
                                wire:model.live="duplicateMode"
                                wire:loading.attr="disabled" wire:target="duplicateMode,duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm">
                            <label class="form-check-label" for="duplicate-mode-skip">Skip</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="duplicate-mode-overwrite" value="overwrite"
                                wire:model.live="duplicateMode"
                                wire:loading.attr="disabled" wire:target="duplicateMode,duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm">
                            <label class="form-check-label" for="duplicate-mode-overwrite">Overwrite</label>
                        </div>
                    </div>

                    <div wire:loading.flex wire:target="duplicateMode,duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm"
                        class="align-items-center justify-content-center border rounded p-3 mb-3 text-muted gap-2">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Calculating preview stats...
                    </div>

                    <div wire:loading.remove wire:target="duplicateMode,duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm">
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block">Total Types</small>
                                    <strong>{{ $duplicatePreviewStats['total_types'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block">Source Activities</small>
                                    <strong>{{ $duplicatePreviewActivities }}</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block">Source Questions</small>
                                    <strong>{{ $duplicatePreviewQuestions }}</strong>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block">Existing In Target</small>
                                    <strong>{{ $duplicatePreviewStats['existing_activities'] ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block">
                                        {{ $duplicateMode === 'overwrite' ? 'Activities To Process' : 'Activities To Create' }}
                                    </small>
                                    <strong>{{ $duplicateMode === 'overwrite' ? ($duplicatePreviewStats['processed_activities'] ?? 0) : ($duplicatePreviewStats['copiable_activities'] ?? 0) }}</strong>
                                    activities
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2 h-100 bg-light">
                                    <small class="text-muted d-block">
                                        {{ $duplicateMode === 'overwrite' ? 'Questions To Apply' : 'Questions To Copy' }}
                                    </small>
                                    <strong>{{ $duplicateMode === 'overwrite' ? ($duplicatePreviewStats['processed_questions'] ?? 0) : ($duplicatePreviewStats['copiable_questions'] ?? 0) }}</strong>
                                    questions
                                </div>
                            </div>
                        </div>

                        @if($duplicateMode === 'overwrite')
                            <div class="row g-2 mb-3">
                                <div class="col-12">
                                    <div class="border rounded p-2 h-100 bg-light">
                                        <small class="text-muted d-block">Activities To Overwrite</small>
                                        <strong>{{ $duplicatePreviewStats['overwritten_activities'] ?? 0 }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($duplicatePreviewStats['by_type']))
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th class="text-center">Activities</th>
                                            <th class="text-center">Questions</th>
                                            <th class="text-center">Existing</th>
                                            <th class="text-center">Activities To Create</th>
                                            <th class="text-center">Questions To Copy</th>
                                            @if($duplicateMode === 'overwrite')
                                                <th class="text-center">Activities To Overwrite</th>
                                                <th class="text-center">Questions To Apply</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($duplicatePreviewStats['by_type'] as $typeKey => $stats)
                                            <tr>
                                                <td>{{ Str::title(Str::replace('_', ' ', $typeKey)) }}</td>
                                                <td class="text-center">{{ $stats['activities'] }}</td>
                                                <td class="text-center">{{ $stats['questions'] }}</td>
                                                <td class="text-center">{{ $stats['existing_activities'] }}</td>
                                                <td class="text-center">{{ $stats['copiable_activities'] }}</td>
                                                <td class="text-center">{{ $stats['copiable_questions'] }}</td>
                                                @if($duplicateMode === 'overwrite')
                                                    <td class="text-center">{{ $stats['overwritten_activities'] }}</td>
                                                    <td class="text-center">{{ $stats['processed_questions'] }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif($duplicateSourceTerm)
                            <div class="small text-muted">No matching activities found for the selected filters.</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" wire:click="duplicateQuestions" class="btn btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="duplicateQuestions,duplicateMode,duplicateSourceTerm,selectedDuplicateTypes,duplicateTargetTerm"
                        @disabled(!$duplicateSourceTerm || !$duplicateTargetTerm || (($duplicateMode === 'overwrite' ? ($duplicatePreviewStats['processed_activities'] ?? 0) : ($duplicatePreviewStats['copiable_activities'] ?? 0)) < 1))>
                        <span wire:loading.remove wire:target="duplicateQuestions">Copy Now</span>
                        <span wire:loading wire:target="duplicateQuestions">
                            <span class="spinner-border spinner-border-sm"></span> Copying...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div wire:ignore.self class="modal" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel"
        aria-hidden="true">
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('openDuplicateTermModal', function () {
                var el = document.getElementById('duplicateTermModal');
                if (!el) {
                    return;
                }

                var modal = new bootstrap.Modal(el);
                modal.show();
            });

            window.addEventListener('duplicateTermComplete', function (e) {
                var d = e.detail || {};
                var msg = 'Duplicate complete. Created activities: ' + (d.copied || 0) + ', Overwritten activities: ' + (d.overwritten || 0) + ', Skipped existing: ' + (d.skipped || 0) + ', Applied questions: ' + (d.questionsCopied || 0);

                alert(msg);

                var el = document.getElementById('duplicateTermModal');
                if (el) {
                    var instance = bootstrap.Modal.getInstance(el);
                    if (instance) {
                        instance.hide();
                    }
                }
            });
        });
    </script>
</div>