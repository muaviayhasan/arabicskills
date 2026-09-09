<div>
    <x-admin.list-toolbar>
        @if (getPermissions('admin_roles', 'add'))
            <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal"
                data-bs-target="#addRole">
                <i class='bx bx-plus-circle'></i> Add New Role
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
                                <tr>
                                    <x-admin.sortable-header field="role" label="Role"
                                        :current="$sortField" :direction="$sortDirection" />
                                    <th>Permission</th>
                                    <th>View</th>
                                    <th>Add</th>
                                    <th>Edit</th>
                                    <th>Delete</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr class="alert-success">
                                        <th scope="row">{{ $role->name }}</th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <td class="d-flex">
                                            @if (getPermissions('admin_roles', 'edit'))
                                                <a href="{{ route('admin.edit-role-permission', ['role_id' => $role->id]) }}"
                                                    class="btn btn-success"><i
                                                        class="bx bx-edit"></i>&nbsp;Edit</a>
                                            @endif
                                            @if (getPermissions('admin_roles', 'delete'))
                                                <button type="button"
                                                    wire:click.prevent="deleteRole({{ $role->id }})"
                                                    class="ms-2 btn btn-danger"><i class='bx bx-trash'></i>&nbsp;Delete</button>
                                            @endif
                                        </td>
                                    </tr>
                                    @foreach ($role->AdminRolePermission as $permission)
                                        <tr>
                                            <th></th>
                                            <td>{{ $permission->AdminPermission->name }}</td>


                                            <td>{!! $permission->AdminPermission->view
                                                ? ($permission->view
                                                    ? "<i class='bx bx-check text-success'></i>"
                                                    : "<i class='bx bx-x text-danger'></i>")
                                                : "<i class='bx bx-block'></i>" !!}
                                            </td>

                                            <td>{!! $permission->AdminPermission->add
                                                ? ($permission->add
                                                    ? "<i class='bx bx-check text-success'></i>"
                                                    : "<i class='bx bx-x text-danger'></i>")
                                                : "<i class='bx bx-block'></i>" !!}
                                            </td>

                                            <td>{!! $permission->AdminPermission->edit
                                                ? ($permission->edit
                                                    ? "<i class='bx bx-check text-success'></i>"
                                                    : "<i class='bx bx-x text-danger'></i>")
                                                : "<i class='bx bx-block'></i>" !!}
                                            </td>

                                            <td>{!! $permission->AdminPermission->delete
                                                ? ($permission->delete
                                                    ? "<i class='bx bx-check text-success'></i>"
                                                    : "<i class='bx bx-x text-danger'></i>")
                                                : "<i class='bx bx-block'></i>" !!}
                                            </td>

                                            <td></td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                        <div>
                            <div class="float-end mt-2">
                                {{ $roles->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    <!-- add role Modal -->
    <div class="modal fade" wire:ignore.self id="addRole" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" role="dialog" aria-labelledby="addRoleLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRoleLabel">Add New Employee Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input id="new_role" name="new_role" wire:model.defer="new_role" class="form-control me-auto"
                        type="text" placeholder="Enter role name">
                    @error('new_role')
                        <span class="ms-3 text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" wire:click.prevent="addRole" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>

</div>
