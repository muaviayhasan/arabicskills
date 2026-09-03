<div>
    <!-- Search Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label">School</label>
                            <select wire:model="school_id" class="form-select bg-light border-light rounded">
                                <option value="">All Schools</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label">Grade</label>
                            <select wire:model="grade_id" class="form-select bg-light border-light rounded">
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
                            <label class="form-label">Round</label>
                            <select wire:model="term" class="form-select bg-light border-light rounded">
                                <option value="">All Rounds</option>
                                @foreach ($terms as $termOption)
                                    <option value="{{ $termOption }}">{{ $termOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label">Status</label>
                            <select wire:model="status" class="form-select bg-light border-light rounded">
                                <option value="">All Status</option>
                                <option value="attempted">Attempted</option>
                                <option value="unattempted">Unattempted</option>
                                <option value="expired">Expired</option>
                                <option value="absent">Absent</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label">Academic Year</label>
                            <x-student-academic-year-select wire:model="year" />
                        </div>
                        <div class="col-12 col-md-4 col-lg-3">
                            <label class="form-label">Archive (Students & Exams)</label>
                            <select wire:model="archiveStatus" class="form-select bg-light border-light rounded">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-8 col-lg-9">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="searchWord"
                                class="form-control bg-light border-light rounded"
                                placeholder="Name, Registration, Username...">
                        </div>
                        <div class="col-12">
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
        <button wire:click="exportSearched" class="btn btn-success" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="exportSearched">
                <i class="fa fa-file-excel"></i> Export
            </span>
            <span wire:loading wire:target="exportSearched">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Exporting...
            </span>
        </button>
        <button wire:click="reassignAllExpired" class="btn btn-danger" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="reassignAllExpired">
                <i class="fa fa-redo"></i> Re-assign All Expired Attempts
            </span>
            <span wire:loading wire:target="reassignAllExpired">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Re-assigning...
            </span>
        </button>
    </x-admin.list-toolbar>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-centered align-middle table-nowrap table-check"
                            style="margin-bottom: 70px !important">
                            <thead>
                                <tr class="fw-semibold text-center">
                                    <th>#</th>
                                    <th>School</th>
                                    <th>Level</th>
                                    <th>Term</th>
                                    <th>Student</th>
                                    <th>Date</th>
                                    <th>Action</th>

                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($exams as $exam)
                                    <tr class="text-center">
                                        <td>{{ $loop->iteration + $exams->firstItem() - 1 }}</td>

                                        <td>
                                            {{ $exam->Exam?->School?->name ?? '' }}
                                        </td>

                                        <td>
                                            {{ \App\Support\ExamLevelHelper::levelNamesLabel($exam->Exam?->level_ids ?? []) }}<br />
                                            {{ $exam->Exam?->Grade?->name ?? '' }} -
                                            {{ $exam->Student?->Section?->name ?? '' }}
                                        </td>
                                        <td class="fw-semibold">{{ ucwords($exam->Exam?->term ?? '') }}
                                        </td>
                                        <td>
                                            {{ $exam->Student?->name ?? '' }}
                                            <br><span class="text-info">{{ $exam->Student?->user_name ?? '' }}</span>
                                        </td>

                                        <td>

                                            <small class="text-muted">
                                                Created: {{ date('d M, Y H:i', strtotime($exam->created_at)) }} <br />
                                                {{ ! is_null($exam->updated_at) ? 'Updated: ' . date('d M, Y H:i', strtotime($exam->updated_at)) : '' }}
                                            </small>
                                        </td>

                                        <td>
                                            @if (($exam->take_exam_count ?? 0) > 0)
                                                <a href="{{ route('admin.student-exam.details', $exam->id) }}"
                                                    class="btn btn-danger">
                                                    Details</a>
                                            @else
                                                <span class="badge bg-secondary">Absent</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td class="bg-white" colspan="7">

                                            <img src="{{ asset('assets/images/empty.png') }}" alt="Empty List Image"
                                                width="25%">
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <div class="float-end">
                            {{ $exams->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>