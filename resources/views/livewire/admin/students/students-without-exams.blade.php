<div>
    <!-- School selector -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label fw-semibold">Select School</label>
                            <select wire:model.live="school_id" class="form-select bg-light border-light rounded">
                                <option value="">-- Select a School --</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <label class="form-label fw-semibold">Exam Round <small class="text-muted">(optional)</small></label>
                            <select wire:model.live="term" class="form-select bg-light border-light rounded">
                                <option value="">-- All Rounds --</option>
                                @foreach (unserialize(config('options.terms')) as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($school_id)
                            <div class="col-12 col-md-6 col-lg-4">
                                <span class="badge bg-warning fs-6">
                                    <i class="bx bx-user-x me-1"></i>
                                    {{ $students->total() }} student(s) have no active/pending exam
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!$school_id)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bx bxs-school fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Please select a school above to see students without exams.</h5>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-centered align-middle table-nowrap table-check"
                                style="margin-bottom: 70px;">
                                <thead>
                                    <tr class="fw-semibold text-center">
                                        <th>#</th>
                                        <x-admin.sortable-header field="name" label="Name"
                                            :current="$sortField" :direction="$sortDirection" />
                                        <x-admin.sortable-header field="registration" label="Reg/Username"
                                            :current="$sortField" :direction="$sortDirection" />
                                        <x-admin.sortable-header field="grade" label="Grade"
                                            :current="$sortField" :direction="$sortDirection" />
                                        <x-admin.sortable-header field="level" label="Level"
                                            :current="$sortField" :direction="$sortDirection" />
                                        <x-admin.sortable-header field="section" label="Section"
                                            :current="$sortField" :direction="$sortDirection" />
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($students as $student)
                                        <tr class="text-center">
                                            <td>{{ $loop->iteration + $students->firstItem() - 1 }}</td>
                                            <td class="fw-semibold">{{ $student->name }}</td>
                                            <td>
                                                {{ $student->registration }}<br>
                                                <span class="text-success">{{ $student->user_name }}</span>
                                            </td>
                                            <td>
                                                {{ $student->Grade?->name ?? '' }}
                                            </td>
                                            <td>
                                                {{ $student->assignedLevel?->name ?? 'N/A' }}
                                            </td>
                                            <td>{{ $student->Section?->name ?? '' }}</td>
                                            <td>
                                                <a href="{{ route('admin.edit-student', $student->id) }}"
                                                    class="btn btn-sm btn-info">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bx bx-check-circle fs-3 text-success d-block mb-2"></i>
                                                All students at this school have been assigned to an active or pending exam.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3">
                            {{ $students->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
