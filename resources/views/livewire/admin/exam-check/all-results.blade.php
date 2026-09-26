<div>

    {{-- School, Grade and Level, three across one row --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">School</label>
                            <select class="form-control" wire:model.live="searchSchool" wire:change="search">
                                <option value="">All Schools</option>
                                @foreach ($schools as $sch)
                                    <option value="{{ $sch->id }}">{{ $sch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Grade</label>
                            <select class="form-control" wire:model.live="grade_id" wire:change="search">
                                <option value="">All Grades</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Level</label>
                            <select class="form-control" wire:model.live="level_id" wire:change="search">
                                <option value="">All Levels</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label">Academic Year</label>
                            <x-student-academic-year-select class="form-control" wire:model.live="year"
                                wire:change="search" />
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label">Status</label>
                            <select wire:model.live="archiveStatus" wire:change="search" class="form-control"
                                title="Applies to both students and exams">
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                                <option value="all">All</option>
                            </select>
                        </div>

                        {{-- These two keep their ids and wire:ignore: the search is driven
                             by jQuery in the layout, which binds to #searchColumn and #searchWord. --}}
                        <div class="col-12 col-md-3">
                            <label class="form-label">Filter By</label>
                            <select id="searchColumn" class="form-control" wire:ignore>
                                <option value="">Select</option>
                                <option value="term">Exam Term</option>
                                <option value="grade">Exam Grade</option>
                                <option value="section">Student Section</option>
                                <option value="level">Exam Level</option>
                                <option value="student">Student Registration</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" id="searchWord" class="form-control" wire:ignore
                                placeholder="Type search term">
                            <span id="searchColumnError" class="text-danger small"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-admin.list-toolbar>
        <button class="btn btn-success" type="button" wire:click.prevent="exportExcel">
            <i class='bx bx-spreadsheet align-middle'></i> Export
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
                                    <x-admin.sortable-header field="term" label="Term"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="student" label="Student"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Reading Exam</th>
                                    <th>Listening Exam</th>
                                    <th>writing Exam</th>
                                    <th>Speaking Exam</th>
                                    <th>Sentences Structures Exam</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($exams as $exam)
                                <tr class="text-center">

                                    <td class="fw-semibold">{{ $exam->Exam->term ?? '' }}
                                    </td>
                                    <td>
                                        {{ $exam->Student->name ?? '' }}
                                        <br><span class="text-info">{{ $exam->Student->registration ?? '' }}</span>
                                    </td>

                                    <td>
                                        @if (collect(optional($exam->Result)->reading_marks)->sum() ||
                                        ($exam->reading_status['status'] ?? 'unattempted') == 'attempted')
                                        <a href="{{ route('admin.show-exam', ['type' => 'reading', 'exam_id' => $exam->id]) }}"
                                            class='text-primary'>{{
                                            collect(optional($exam->Result)->reading_marks)->sum() ?? 'Click' }}</a>
                                        @else
                                        <span class="text-success">{{ $exam->reading_status['status'] ?? 'unattempted'
                                            }}
                                        </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (collect(optional($exam->Result)->listening_marks)->sum() ||
                                        ($exam->listening_status['status'] ?? 'unattempted') == 'attempted')
                                        <a href="{{ route('admin.show-exam', ['type' => 'listening', 'exam_id' => $exam->id]) }}"
                                            class='text-primary'>{{
                                            collect(optional($exam->Result)->listening_marks)->sum() ?? 'Click' }}</a>
                                        @else
                                        <span class="text-success">{{ $exam->listening_status['status'] ?? 'unattempted'
                                            }}
                                        </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (collect(optional($exam->Result)->writing_marks)->sum() ||
                                        ($exam->writing_status['status'] ?? 'unattempted') == 'attempted')
                                        <a href="{{ route('admin.show-exam', ['type' => 'writing', 'exam_id' => $exam->id]) }}"
                                            class='text-primary'>{{
                                            collect(optional($exam->Result)->writing_marks)->sum() ?? 'Click' }}</a>
                                        @else
                                        <span class="text-success">{{ $exam->writing_status['status'] ?? 'unattempted'
                                            }}
                                        </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (collect(optional($exam->Result)->speaking_marks)->sum() ||
                                        ($exam->speaking_status['status'] ?? 'unattempted') == 'attempted')
                                        <a href="{{ route('admin.show-exam', ['type' => 'speaking', 'exam_id' => $exam->id]) }}"
                                            class='text-primary'>{{
                                            collect(optional($exam->Result)->speaking_marks)->sum() ?? 'Click' }}</a>
                                        @else
                                        <span class="text-success">{{ $exam->speaking_status['status'] ?? 'unattempted'
                                            }}
                                        </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (collect(optional($exam->Result)->sentences_structures_marks)->sum() ||
                                        ($exam->sentences_structures_status['status'] ?? 'unattempted') == 'attempted')
                                        <a href="{{ route('admin.show-exam', ['type' => 'sentences_structures', 'exam_id' => $exam->id]) }}"
                                            class='text-primary'>{{
                                            collect(optional($exam->Result)->sentences_structures_marks)->sum() ?? 'Click' }}</a>
                                        @else
                                        <span class="text-success">{{ $exam->sentences_structures_status['status'] ?? 'unattempted'
                                            }}
                                        </span>
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
    <div wire:loading wire:target="exportExcel" class="modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>
                </div>
            </div>
        </div>
    </div>
</div>