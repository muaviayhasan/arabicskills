<div>

    <x-admin.list-toolbar>
        <select id="searchColumn" class="form-control bg-light border-light rounded" wire:ignore style="max-width: 180px;">
            <option value="">Filter By</option>
            <option value="id">Id</option>
            <option value="email">Email</option>
            <option value="name">Name</option>
        </select>
        <input type="text" id="searchWord" class="form-control bg-light border-light rounded" wire:ignore
            placeholder="Type search term" style="max-width: 220px;">
        <span id="searchColumnError" class="text-danger align-self-center"></span>
        <a href="{{ route('admin.add-admin') }}" class="btn btn-primary">
            <i class='bx bx-plus-circle'></i> Add
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
                                    <th style="width: 90px;">
                                        Image
                                    </th>
                                    <x-admin.sortable-header field="name" label="Name"
                                        :current="$sortField" :direction="$sortDirection"
                                        style="width: 210px;" />
                                    <x-admin.sortable-header field="email" label="Email"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="school" label="School"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="role" label="Role"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($admins as $admin)
                                    <tr class="text-center">
                                        <td>
                                            <button type="button" class="imgButtons btn p-0  border-0 shadow-none"
                                                data-bs-toggle="modal" data-bs-target="#imageModal"
                                                data-image="{{ image('uploads/admins', $admin->image) }}">

                                                <div class="rounded border-0 table-img">
                                                    <img src="{{ image('uploads/admins', $admin->image) }}"
                                                        class="rounded" alt="image" width="100%" height="100%">
                                                </div>

                                            </button>
                                        </td>
                                        <td class="fw-semibold">{{ $admin->first_name . ' ' . $admin->last_name }}
                                        </td>
                                        <td>
                                            {{ $admin->email }}
                                        </td>
                                        <td>
                                            {{ $admin->school?->name ?? 'All Schools' }}
                                        </td>
                                        <td>
                                            {{ $admin->adminRole->name }}
                                        </td>

                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-danger light sharp"
                                                    data-bs-toggle="dropdown">
                                                    <svg width="20px" height="20px" viewBox="0 0 24 24"
                                                        version="1.1">
                                                        <g stroke="none" stroke-width="1" fill="none"
                                                            fill-rule="evenodd">
                                                            <rect x="0" y="0" width="24" height="24" />
                                                            <circle fill="#000000" cx="5" cy="12"
                                                                r="2" />
                                                            <circle fill="#000000" cx="12" cy="12"
                                                                r="2" />
                                                            <circle fill="#000000" cx="19" cy="12"
                                                                r="2" />
                                                        </g>
                                                    </svg>
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if (getPermissions('admins', 'edit'))
                                                        <a href="{{ route('admin.edit-admin', ['admin_id' => $admin->id]) }}"
                                                            class="dropdown-item text-center">Edit</a>
                                                    @endif
                                                    @if (getPermissions('admins', 'delete'))
                                                        <a class="dropdown-item text-center"
                                                            wire:click.prevent="deleteAdmin({{ $admin->id }})"
                                                            href="#">Delete</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3">
                        <div class="float-end">
                            {{ $admins->links() }}
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
