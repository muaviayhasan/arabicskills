<div>
    <div wire:ignore.self class="modal fade" id="bulkCreateExamModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="bulkCreateExamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkCreateExamModalLabel">
                        <i class="bx bx-layer-plus"></i> Bulk Create Exams
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="resetForm"></button>
                </div>

                <div class="modal-body">
                    {{-- ============== Form ============== --}}
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">School <span class="text-danger">*</span></label>
                            <select wire:model.live="inputs.school_id" class="form-select">
                                <option value="">Select school</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                            @error('inputs.school_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Term / Round <span class="text-danger">*</span></label>
                            <select wire:model.live="inputs.term" class="form-select">
                                <option value="">Select term</option>
                                @foreach (unserialize(config('options.terms')) as $term)
                                    <option value="{{ $term }}">{{ $term }}</option>
                                @endforeach
                            </select>
                            @error('inputs.term')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Grade <span class="text-muted small">(optional)</span></label>
                            <select wire:model.live="inputs.grade_id" class="form-select">
                                <option value="">All grades</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                            @error('inputs.grade_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Level <span class="text-muted small">(optional — all levels if empty)</span></label>
                            <select wire:model.live="inputs.level_id" class="form-select">
                                <option value="">All levels</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                            @error('inputs.level_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- ============== Assessment selection ============== --}}
                    <div class="mb-2">
                        <label class="form-label fw-semibold mb-2">Assessments <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @php
                                $typeLabels = [
                                    'reading'              => 'Reading',
                                    'listening'            => 'Listening',
                                    'writing'              => 'Writing',
                                    'speaking'             => 'Speaking',
                                    'sentences_structures' => 'Sentences & Structures',
                                ];
                            @endphp
                            @foreach ($availableTypes as $type)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $type }}"
                                        id="bulkType_{{ $type }}" wire:model.live="inputs.activity_types">
                                    <label class="form-check-label" for="bulkType_{{ $type }}">
                                        {{ $typeLabels[$type] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('inputs.activity_types')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                    </div>

                    {{-- ============== Per-type time inputs (only when type is selected) ============== --}}
                    @if (! empty($inputs['activity_types']))
                        <div class="row g-3 mt-1">
                            @foreach ($inputs['activity_types'] as $type)
                                <div class="col-md-4 col-lg-3">
                                    <label class="form-label">{{ $typeLabels[$type] }} time (HH:MM)</label>
                                    <input type="text" class="form-control"
                                        wire:model.defer="inputs.{{ $type }}_time" placeholder="00:00">
                                    @error('inputs.' . $type . '_time')<small class="text-danger">{{ $message }}</small>@enderror
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary" wire:click="loadPreview"
                            wire:loading.attr="disabled" wire:target="loadPreview">
                            <span wire:loading.remove wire:target="loadPreview"><i class="bx bx-search-alt"></i> Load Preview</span>
                            <span wire:loading wire:target="loadPreview">
                                <span class="spinner-border spinner-border-sm"></span> Loading...
                            </span>
                        </button>
                    </div>

                    {{-- ============== Preview ============== --}}
                    @if (! empty($preview))
                        <hr class="my-3">
                        <h6 class="fw-semibold mb-2">Preview</h6>

                        @php
                            $totalCreate = collect($preview)->where('action', 'create')->count();
                            $totalUpdate = collect($preview)->where('action', 'update')->count();
                            $totalSkip   = collect($preview)->where('action', 'skip')->count();
                        @endphp

                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-success">Create: {{ $totalCreate }}</span>
                            <span class="badge bg-info">Update: {{ $totalUpdate }}</span>
                            <span class="badge bg-secondary">Skip: {{ $totalSkip }}</span>
                            <span class="badge bg-light text-dark">Total: {{ count($preview) }}</span>
                        </div>

                        <div class="table-responsive" style="max-height: 380px;">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>Grade</th>
                                        <th>Level</th>
                                        <th>Students</th>
                                        <th>Existing Exam</th>
                                        <th>Will Add</th>
                                        <th>Already Has</th>
                                        <th>No Bank</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview as $row)
                                        <tr>
                                            <td>{{ $row['grade_name'] }}</td>
                                            <td>{{ $row['level_label'] }}</td>
                                            <td>{{ $row['student_count'] }}</td>
                                            <td>
                                                @if ($row['existing_exam'])
                                                    <span class="badge bg-info text-uppercase">{{ $row['existing_status'] }}</span>
                                                    #{{ $row['existing_exam'] }}
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @forelse ($row['missing_types'] as $t)
                                                    <span class="badge bg-success me-1">{{ $typeLabels[$t] ?? $t }}</span>
                                                @empty
                                                    <span class="text-muted">—</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                @forelse ($row['existing_types'] as $t)
                                                    <span class="badge bg-secondary me-1">{{ $typeLabels[$t] ?? $t }}</span>
                                                @empty
                                                    <span class="text-muted">—</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                @forelse ($row['skipped_types'] as $t)
                                                    <span class="badge bg-warning text-dark me-1">{{ $typeLabels[$t] ?? $t }}</span>
                                                @empty
                                                    <span class="text-muted">—</span>
                                                @endforelse
                                            </td>
                                            <td>
                                                @switch($row['action'])
                                                    @case('create')
                                                        <span class="badge bg-success">Create</span>
                                                        @break
                                                    @case('update')
                                                        <span class="badge bg-info">Update</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">Skip</span>
                                                @endswitch
                                                <div class="small text-muted">{{ $row['reason'] }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="resetForm">Close</button>
                    <button type="button" class="btn btn-primary" wire:click="createBulkExams"
                        @if (empty($preview) || (collect($preview)->where('action', '!=', 'skip')->count() === 0)) disabled @endif
                        wire:loading.attr="disabled" wire:target="createBulkExams">
                        <span wire:loading.remove wire:target="createBulkExams">
                            <i class="bx bx-check-double"></i> Bulk Create Exams
                        </span>
                        <span wire:loading wire:target="createBulkExams">
                            <span class="spinner-border spinner-border-sm"></span> Processing...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
