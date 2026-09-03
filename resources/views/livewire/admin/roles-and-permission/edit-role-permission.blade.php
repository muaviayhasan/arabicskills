<div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Update {{ $role->name }} Or Its Permissions
                        <a href="{{ route('admin.role-permission') }}" class="btn btn-info float-end"><i
                                class='bx bx-arrow-back me-1'></i>Back</a>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center align-items-center g-2 mb-3">
                        <div class="col-12 col-md-10 justify-content-center align-items-center">
                            <div class="d-flex-col d-md-flex text-center">
                                <div class="w-100 md-w-75 mb-1 mb-md-0 me-0 me-md-3">
                                    <input wire:model.defer="name" type="text" class="form-control w-full"
                                        placeholder="Role Name">
                                    @error('name')
                                        <p class="text-danger ms-3">Role must have a name</p>
                                    @enderror
                                </div>
                                <div class="w-100 md-w-25 mb-1 mb-md-0 me-0 me-md-3">
                                    <button type="button" class="btn btn-primary w-100"
                                        wire:click.prevent="updateRole">Update Role Name</button>
                                </div>
                                <div class="w-100 md-w-25 mb-1 mb-md-0">
                                    <button type="button" class="btn btn-danger w-100"
                                        wire:click.prevent="deleteRole">Delete Role</button>
                                </div>
                            </div>

                        </div>
                    </div>

                    @php
                        $i = 0;
                    @endphp
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Permission</th>
                                    <th>View</th>
                                    <th>Add</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allPermissions as $index => $permission)
                                    @php
                                        $hasPermission = $permission->roles->first();
                                    @endphp
                                    <tr>
                                        <th>{{ ++$i }}</th>
                                        <td class="fw-semibold">{{ $permission->name }}</td>

                                        <td>
                                            {!! $permission->view
                                                ? "<input type='checkbox' wire:model.defer='inputs.$index.view'>"
                                                : "<i class='bx bx-block'></i>" !!}

                                        </td>

                                        <td>
                                            {!! $permission->add
                                                ? "<input type='checkbox' wire:model.defer='inputs.$index.add'>"
                                                : "<i class='bx bx-block'></i>" !!}

                                        </td>

                                        <td>
                                            {!! $permission->edit
                                                ? "<input type='checkbox' wire:model.defer='inputs.$index.edit'>"
                                                : "<i class='bx bx-block'></i>" !!}

                                        </td>

                                        <td>
                                            {!! $permission->delete
                                                ? "<input type='checkbox' wire:model.defer='inputs.$index.delete'>"
                                                : "<i class='bx bx-block'></i>" !!}

                                        </td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (getPermissions('admin_roles', 'edit'))
                            <div class="my-3">
                                <button wire:click.prevent="updateRolePermission" class="btn btn-primary float-end">
                                    Update Permissions
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- end row -->
