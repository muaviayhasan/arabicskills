<div>
    <!-- Search Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-9">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="searchWord" class="form-control bg-light border-light rounded"
                                placeholder="Search by Name, Email, or ID...">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            {{-- Spacer matching the Search label, so the buttons line up
                                 with the input rather than sitting below it. --}}
                            <label class="form-label d-none d-md-block" aria-hidden="true">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="manageSearch" class="btn btn-primary flex-fill" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="manageSearch">
                                        <i class="bx bx-search-alt align-middle"></i> Search
                                    </span>
                                    <span wire:loading wire:target="manageSearch">
                                        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                                        Searching...
                                    </span>
                                </button>
                                <button type="button" wire:click="resetFilters" class="btn btn-secondary flex-fill" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="resetFilters">
                                        <i class="bx bx-reset align-middle"></i> Reset
                                    </span>
                                    <span wire:loading wire:target="resetFilters">
                                        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
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
        <a href="{{ route('admin.add-school') }}" class="btn btn-primary">
            <i class='bx bx-plus-circle align-middle'></i> Add
        </a>
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
                                    <th>
                                        Logo
                                    </th>
                                    <x-admin.sortable-header field="name" label="Name"
                                        :current="$sortField" :direction="$sortDirection"
                                        style="width: 210px;" />
                                    <x-admin.sortable-header field="email" label="Email"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="students" label="Students"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Import</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @forelse ($schools as $school)
                                <tr class="text-center">
                                    <td>
                                        <button type="button" class="imgButtons btn p-0  border-0 shadow-none"
                                            data-bs-toggle="modal" data-bs-target="#imageModal"
                                            data-image="{{ read_image($school->logo) }}">

                                            <div class="rounded border-0 table-small-img">
                                                <img src="{{ read_image($school->logo) }}" class="rounded" alt="image"
                                                    width="100%" height="100%">
                                            </div>

                                        </button>
                                    </td>
                                    <td class="fw-semibold">{{ $school->name }}
                                    </td>
                                    <td>
                                        {{ $school->email }}
                                    </td>

                                    <td>
                                        <a href="{{ route('admin.students', ['school_id' => $school->id]) }}"
                                            class="text-primary">{{ $school->student_count }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.import-students', ['school_id' => $school->id]) }}"
                                            class="text-primary">Import Students</a>
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
                                            <div class="dropdown-menu me-5">
                                                @if (getPermissions('schools', 'edit'))
                                                <a href="{{ route('admin.edit-school', ['school_id' => $school->id]) }}"
                                                    class="dropdown-item text-center">Edit</a>
                                                @endif
                                                @if (getPermissions('classes', 'view'))
                                                <a href="{{ route('admin.grades-and-sections', ['school_id' => $school->id]) }}"
                                                    class="dropdown-item text-center">Classes</a>
                                                @endif
                                                @if (getPermissions('schools', 'delete'))
                                                <button type="button"
                                                    class="dropdown-item text-center text-danger"
                                                    wire:click="deleteSchool({{ $school->id }})">Delete</button>
                                                @endif

                                            </div>
                                        </div>
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
                            {{ $schools->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
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



</div>