<div>
    <!-- Search Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label class="form-label">Grade</label>
                            <select wire:model="grade_id"
                                class="form-select bg-light border-light rounded">
                                <option value="">All Grades</option>
                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-5">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="searchWord" class="form-control bg-light border-light rounded"
                                placeholder="Search by section name...">
                        </div>
                        <div class="col-12 col-md-12 col-lg-3">
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="manageSearch" class="btn btn-primary flex-fill" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="manageSearch">
                                        <i class="bx bx-search-alt"></i> Search
                                    </span>
                                    <span wire:loading wire:target="manageSearch">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        Searching...
                                    </span>
                                </button>
                                <button type="button" wire:click="resetFilters" class="btn btn-secondary flex-fill" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="resetFilters">
                                        <i class="bx bx-reset"></i> Reset
                                    </span>
                                    <span wire:loading wire:target="resetFilters">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
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
        @if (getPermissions('classes', 'add'))
            <button type="button" wire:click="previewMergeSections" wire:loading.attr="disabled"
                class="btn btn-warning waves-effect waves-light">
                <span wire:loading.remove wire:target="previewMergeSections">
                    <i class='bx bx-merge'></i> Merge Duplicate Sections
                </span>
                <span wire:loading wire:target="previewMergeSections">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Analysing...
                </span>
            </button>
            <button type="button" wire:click="$set('new_section',[])" class="btn btn-primary waves-effect waves-light"
                data-bs-toggle="modal" data-bs-target="#addSection">
                <i class='bx bx-plus-circle'></i> Add New Section
            </button>
        @endif
    </x-admin.list-toolbar>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr class="text-center align-middle">
                                    <th>Classes(Divisions)</th>
                                    <th>Grade</th>
                                    <th>Total Student</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sections as $section)
                                    <tr class="text-center align-middle">
                                        <td class="fw-bolder">{{ $section->name }}</td>
                                        <td class="text-primary">
                                            {{ $section->Grade?->name ?? '' }}<br>
                                            {{ $section->Grade?->name ?? '' }}
                                        </td>

                                        <td>{{ $section->student_count ?? 0 }}</td>

                                        @if (getPermissions('classes', 'edit'))
                                            <td><button type="button" class="btn btn-success" data-bs-toggle="modal"
                                                    data-bs-target="#editSection"
                                                    wire:click="editSection({{ $section }})"><i
                                                        class='bx bxs-edit'></i> Edit</button>
                                            </td>
                                        @else
                                            <td><i class="bx bx-block text-danger"></i></td>
                                        @endif

                                        @if (getPermissions('classes', 'delete'))
                                            <td><button type="button" class="btn btn-danger"
                                                    wire:click="deleteSection({{ $section->id }})"><i
                                                        class='bx bx-trash'></i> Delete</button></td>
                                        @else
                                            <td><i class="bx bx-block text-danger"></i></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->


    <!-- add section Modal -->
    <div class="modal fade" wire:ignore.self id="addSection" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" role="dialog" aria-labelledby="addSectionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSectionLabel">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center align-items-center g-2">
                        <div class="alert alert-primary alert-dismissible fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                            <strong>You can add one or more section that belong to same grade year(like a b c for grade
                                1). Just type section name press enter to add another.</strong>
                        </div>

                        <div class="col-md-6">
                            <h6>Section Name</h6>
                            <div wire:ignore class="">
                                <select id="names" class="form-control select2Option" style="width: 100%"
                                    multiple="multiple">
                                    @if (isset($new_section['names']))
                                        @foreach ($new_section['names'] as $nm)
                                            <option value="{{ $nm }}" selected>{{ $nm }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>

                            @error('new_section.names')
                                <span
                                    class="ms-3 text-danger">{{ str_replace(['new', 'field', '.'], ['', '', ' '], $message) }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <h6>Grade</h6>
                            <select id="new_section.grade_id" name="new_section.grade_id"
                                wire:model.defer="new_section.grade_id" class="form-control">
                                <option value="">Select Grade</option>
                                @foreach ($classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            @error('new_section.grade_id')
                                <span
                                    class="ms-3 text-danger">{{ str_replace(['new', 'field', '.'], ['', '', ' '], $message) }}</span>
                            @enderror
                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" wire:click.prevent="addSection" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- add section Modal -->
    <div class="modal fade" wire:ignore.self id="editSection" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" role="dialog" aria-labelledby="editSectionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSectionLabel">Update Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center align-items-center g-2">
                        <div class="col-md-6">
                            <h6>Section Name</h6>
                            <input id="edit_section.name" name="edit_section.name"
                                wire:model.defer="edit_section.name" class="form-control" type="text"
                                placeholder="Enter class name">
                            @error('edit_section.name')
                                <span
                                    class="ms-3 text-danger">{{ str_replace(['new', 'field', '.'], ['', '', ' '], $message) }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <h6>Grade</h6>
                            <select id="edit_section.grade_id" name="edit_section.grade_id"
                                wire:model.defer="edit_section.grade_id" class="form-control">
                                <option value="">Select Grade</option>
                                @foreach ($classes as $cls)
                                    <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                                @endforeach
                            </select>
                            @error('edit_section.grade_id')
                                <span
                                    class="ms-3 text-danger">{{ str_replace(['new', 'field', '.'], ['', '', ' '], $message) }}</span>
                            @enderror
                        </div>

                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" wire:click.prevent="updateSection" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Merge Preview Modal -->
    <div class="modal fade" id="mergePreviewModal" wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" role="dialog" aria-labelledby="mergePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title" id="mergePreviewModalLabel">
                        <i class='bx bx-merge me-1'></i> Merge Preview — Review Before Applying
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    @php
                        $hasAnything =
                            count($mergePreview['cross_grade'] ?? []) > 0 ||
                            count($mergePreview['grade_consolidation'] ?? []) > 0 ||
                            count($mergePreview['orphaned'] ?? []) > 0 ||
                            count($mergePreview['same_grade'] ?? []) > 0 ||
                            count($mergePreview['mismatched'] ?? []) > 0;
                    @endphp

                    @if (! $hasAnything)
                        <div class="alert alert-success mb-0">
                            <i class='bx bx-check-circle me-1'></i>
                            No issues found — all sections and student assignments are already clean.
                        </div>
                    @else

                        {{-- Step 1: Cross-grade duplicate sections --}}
                        @if (count($mergePreview['cross_grade'] ?? []) > 0)
                            <h6 class="fw-bold text-danger mb-2">
                                <i class='bx bx-transfer-alt me-1'></i>
                                Cross-Grade Duplicate Sections ({{ count($mergePreview['cross_grade']) }})
                                <small class="text-muted fw-normal ms-1">— Same grade (year) and section name under duplicate grade rows</small>
                            </h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Remove Section</th>
                                            <th>Remove Grade</th>
                                            <th>Keep Section</th>
                                            <th>Keep Grade</th>
                                            <th class="text-center">Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mergePreview['cross_grade'] as $row)
                                            <tr>
                                                <td><span class="badge bg-danger">{{ $row['from_section'] }}</span></td>
                                                <td><small>{{ $row['from_grade'] }}</small></td>
                                                <td><span class="badge bg-success">{{ $row['into_section'] }}</span></td>
                                                <td><small>{{ $row['into_grade'] }}</small></td>
                                                <td class="text-center">{{ $row['students'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Step 1b: Grade consolidation --}}
                        @if (count($mergePreview['grade_consolidation'] ?? []) > 0)
                            <h6 class="fw-bold text-info mb-2">
                                <i class='bx bx-git-merge me-1'></i>
                                Grade Consolidation ({{ count($mergePreview['grade_consolidation']) }})
                                <small class="text-muted fw-normal ms-1">— Sections moved to the canonical numbered grade row</small>
                            </h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-info">
                                        <tr>
                                            <th>Section</th>
                                            <th>From Grade (duplicate row)</th>
                                            <th>To Grade (canonical)</th>
                                            <th class="text-center">Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mergePreview['grade_consolidation'] as $row)
                                            <tr>
                                                <td><strong>{{ $row['section'] }}</strong></td>
                                                <td><small class="text-danger">{{ $row['from_grade'] }}</small></td>
                                                <td><small class="text-success">{{ $row['into_grade'] }}</small></td>
                                                <td class="text-center">{{ $row['students'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Step 2: Orphaned sections --}}
                        @if (count($mergePreview['orphaned'] ?? []) > 0)
                            <h6 class="fw-bold text-warning mb-2">
                                <i class='bx bx-error me-1'></i>
                                Orphaned Sections — Wrong Grade ({{ count($mergePreview['orphaned']) }})
                                <small class="text-muted fw-normal ms-1">— Section's grade_id doesn't match its students' grade</small>
                            </h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Section</th>
                                            <th>Section's Current Grade</th>
                                            <th>Students' Correct Grade</th>
                                            <th class="text-center">Students</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mergePreview['orphaned'] as $row)
                                            <tr>
                                                <td><strong>{{ $row['section'] }}</strong></td>
                                                <td><small class="text-danger">{{ $row['section_grade'] }}</small></td>
                                                <td><small class="text-success">{{ $row['target_grade'] }}</small></td>
                                                <td class="text-center">{{ $row['students'] }}</td>
                                                <td><small>{{ $row['action'] }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Step 3: Same-grade duplicates --}}
                        @if (count($mergePreview['same_grade'] ?? []) > 0)
                            <h6 class="fw-bold text-primary mb-2">
                                <i class='bx bx-duplicate me-1'></i>
                                Same-Grade Duplicate Sections ({{ count($mergePreview['same_grade']) }})
                                <small class="text-muted fw-normal ms-1">— Same grade, same section name</small>
                            </h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Remove Section</th>
                                            <th>Keep Section</th>
                                            <th>Grade</th>
                                            <th class="text-center">Students</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mergePreview['same_grade'] as $row)
                                            <tr>
                                                <td><span class="badge bg-danger">{{ $row['from_section'] }}</span></td>
                                                <td><span class="badge bg-success">{{ $row['into_section'] }}</span></td>
                                                <td><small>{{ $row['grade'] }}</small></td>
                                                <td class="text-center">{{ $row['students'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        {{-- Step 4: Mismatched students --}}
                        @if (count($mergePreview['mismatched'] ?? []) > 0)
                            <h6 class="fw-bold text-secondary mb-2">
                                <i class='bx bx-user-x me-1'></i>
                                Mismatched Student Assignments ({{ count($mergePreview['mismatched']) }})
                                <small class="text-muted fw-normal ms-1">— Student grade_id doesn't match section's grade</small>
                            </h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-bordered align-middle">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Student</th>
                                            <th>Student Grade</th>
                                            <th>Current Section (Wrong Grade)</th>
                                            <th>Will Move To</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mergePreview['mismatched'] as $row)
                                            <tr>
                                                <td>{{ $row['student'] }}</td>
                                                <td><small class="text-success">{{ $row['student_grade'] }}</small></td>
                                                <td><small class="text-danger">{{ $row['current_section'] }}</small></td>
                                                <td><small>{{ $row['correct_section'] }}</small></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    @if ($hasAnything ?? false)
                        <button type="button" class="btn btn-danger"
                            wire:click="mergeDuplicateSections"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="mergeDuplicateSections">
                                <i class='bx bx-check'></i> Confirm &amp; Apply Fixes
                            </span>
                            <span wire:loading wire:target="mergeDuplicateSections">
                                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Applying...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('open-merge-preview-modal', function () {
                var modal = new bootstrap.Modal(document.getElementById('mergePreviewModal'));
                modal.show();
            });
        });
    </script>

</div>
