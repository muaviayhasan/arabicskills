<div>
    {{-- Step 1: what the sheet is for, and the template to fill in --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    {{-- align-items-start, not -end: the Academic Year column carries helper
                         text under its select, so aligning bottoms would push that select
                         higher than the other two. --}}
                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-md-4">
                            <label class="form-label">School</label>
                            <select wire:model.live="school_id" class="form-control">
                                <option value="">Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                            @error('school_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label">Academic Year</label>
                            <select wire:model.live="academic_year" class="form-control">
                                @foreach ($this->years() as $year)
                                    <option value="{{ $year }}">{{ $year }} - {{ $year + 1 }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Which year these marks belong to.</small>
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label">Marks sheet</label>
                            <input wire:model="file" type="file" class="form-control" accept=".xlsx,.xls,.csv">
                            @error('file')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                            <div wire:loading wire:target="file" class="text-muted small mt-1">Uploading…</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <button type="button" wire:click="downloadTemplate" class="btn btn-secondary"
                            wire:loading.attr="disabled">
                            <i class="bx bx-download align-middle"></i> Download Template
                        </button>
                        <button type="button" wire:click="import" class="btn btn-primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="import">
                                <i class="bx bx-upload align-middle"></i> Check Sheet
                            </span>
                            <span wire:loading wire:target="import">
                                <span class="spinner-border spinner-border-sm align-middle" role="status"
                                    aria-hidden="true"></span>
                                Checking…
                            </span>
                        </button>
                        <a href="{{ route('admin.assessment-marks') }}" class="btn btn-light">
                            <i class="bx bx-list-ul align-middle"></i> View Imported Marks
                        </a>
                    </div>

                    <p class="text-muted small mb-0 mt-3">
                        The template already contains your students. Enter marks out of
                        {{ \App\Support\MarkRanges::MAX[\App\Support\MarkRanges::SKILL] }} for each skill.
                        Leave a whole round empty if it has not been assessed. Judgments are worked out for you.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Step 2: review before anything is saved --}}
    @if ($rows)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body pb-0">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <span class="badge bg-success fs-6 fw-normal">{{ $this->readyCount() }} ready</span>
                            @if ($this->problemCount())
                                <span class="badge bg-danger fs-6 fw-normal">{{ $this->problemCount() }} with problems</span>
                                <button type="button" wire:click="downloadSkipped" class="btn btn-sm btn-outline-danger">
                                    <i class="bx bx-download align-middle"></i> Download problem rows
                                </button>
                            @endif
                            <span class="text-muted small ms-auto">Nothing is saved until you confirm.</span>
                        </div>

                        @if ($summary)
                            <div class="alert alert-success">
                                Saved for <strong>{{ $summary['students'] }}</strong> students —
                                new: <strong>{{ $summary['new'] }}</strong>,
                                updated: <strong>{{ $summary['updated'] }}</strong>,
                                unchanged: <strong>{{ $summary['unchanged'] }}</strong>,
                                skipped: <strong>{{ $summary['skipped'] }}</strong>.
                            </div>
                        @endif
                    </div>

                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle table-nowrap">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th>Row</th>
                                        <th>Student</th>
                                        <th>Grade</th>
                                        @foreach (\App\Support\MarkRanges::ROUNDS as $number => $label)
                                            <th>{{ $label }}</th>
                                        @endforeach
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        <tr class="text-center">
                                            <td>{{ $row['row'] }}</td>
                                            <td class="text-start">
                                                <span class="fw-semibold">{{ $row['student_name'] ?: '—' }}</span>
                                                <br /><small class="text-muted">{{ $row['registration'] }}</small>
                                            </td>
                                            <td>{{ $row['grade'] }}{{ $row['section'] ? ' / ' . $row['section'] : '' }}</td>

                                            @foreach (\App\Support\MarkRanges::ROUNDS as $number => $label)
                                                <td>
                                                    @if (isset($row['rounds'][$number]))
                                                        @php($details = $row['rounds'][$number])
                                                        <small class="d-block text-muted">
                                                            {{ implode(' / ', array_map(fn($m) => rtrim(rtrim(number_format($m, 2, '.', ''), '0'), '.'), $details['marks'])) }}
                                                        </small>
                                                        <strong>{{ rtrim(rtrim(number_format($details['total'], 2, '.', ''), '0'), '.') }}</strong>
                                                        <x-admin.judgement-badge :judgement="$details['judgement']" small />
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            <td class="text-start">
                                                @if ($row['status'] === 'ready')
                                                    <span class="badge bg-success fw-normal">Ready</span>
                                                @else
                                                    <span class="badge bg-danger fw-normal">Problem</span>
                                                    @foreach ($row['errors'] as $error)
                                                        <small class="d-block text-danger">{{ $error }}</small>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col text-end">
                <a href="{{ route('admin.assessment-marks') }}" class="btn btn-danger">
                    <i class="bx bx-x me-1 align-middle"></i> Cancel
                </a>
                <button type="button" wire:click="confirmImport" class="btn btn-success"
                    wire:loading.attr="disabled" @disabled($this->readyCount() === 0)>
                    <span wire:loading.remove wire:target="confirmImport">
                        <i class="bx bx-save me-1 align-middle"></i> Confirm and Save {{ $this->readyCount() }} rows
                    </span>
                    <span wire:loading wire:target="confirmImport">
                        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                        Saving…
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
