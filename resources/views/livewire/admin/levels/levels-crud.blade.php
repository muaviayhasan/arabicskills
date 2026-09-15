<div>
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-lg-9">
                            <label class="form-label">Search</label>
                            <input type="text" wire:model="searchWord" class="form-control bg-light border-light rounded"
                                placeholder="Search by level name...">
                        </div>
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="manageSearch" class="btn btn-primary flex-fill" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="manageSearch">
                                        <i class="bx bx-search-alt align-middle"></i> Search
                                    </span>
                                    <span wire:loading wire:target="manageSearch">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        Searching...
                                    </span>
                                </button>
                                <button type="button" wire:click="resetFilters" class="btn btn-secondary flex-fill" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="resetFilters">
                                        <i class="bx bx-reset align-middle"></i> Reset
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
        @if (getPermissions('levels', 'add'))
            <button type="button" wire:click="openCreateModal" class="btn btn-primary waves-effect waves-light"
                data-bs-toggle="modal" data-bs-target="#levelModal">
                <i class='bx bx-plus-circle align-middle'></i> Add Level
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
                                    <x-admin.sortable-header field="id" label="ID"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="name" label="Level Name"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <x-admin.sortable-header field="admin" label="Last Updated By"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($levels as $level)
                                    <tr class="text-center align-middle">
                                        <td>{{ $level->id }}</td>
                                        <td class="fw-bolder">{{ $level->name }}</td>
                                        <td class="text-primary">
                                            {{ $level->Admin?->first_name . ' ' . $level->Admin?->last_name }}
                                        </td>
                                        @if (getPermissions('levels', 'edit'))
                                            <td>
                                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                                    data-bs-target="#levelModal"
                                                    wire:click="openEditModal({{ $level->id }})">
                                                    <i class='bx bxs-edit align-middle'></i> Edit
                                                </button>
                                            </td>
                                        @else
                                            <td><i class="bx bx-block text-danger"></i></td>
                                        @endif
                                        @if (getPermissions('levels', 'delete'))
                                            <td>
                                                <button type="button" class="btn btn-danger"
                                                    wire:click="deleteLevel({{ $level->id }})">
                                                    <i class='bx bx-trash align-middle'></i> Delete
                                                </button>
                                            </td>
                                        @else
                                            <td><i class="bx bx-block text-danger"></i></td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr class="text-center">
                                        <td class="bg-white" colspan="5">
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
                            {{ $levels->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" wire:ignore.self id="levelModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" role="dialog" aria-labelledby="levelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="levelModalLabel">
                        {{ $editingLevelId ? 'Update Level' : 'Add Level' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <h6>Number</h6>
                        <input wire:model.defer="number" class="form-control" type="number" min="1"
                            placeholder="Enter level number">
                        <small class="text-muted">Level name will be generated automatically, e.g. Level 1.</small>
                        @error('number')
                            <span class="d-block ms-1 text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" wire:click.prevent="saveLevel" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>
