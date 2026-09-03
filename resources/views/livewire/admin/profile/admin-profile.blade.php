<div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3>
                        Profile Information
                    </h3>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mx-auto position-reletive">
                                        @if (isset($admin['image']) && !is_string($admin['image']))
                                            <div class="product-img rounded">

                                                <img src="{{ $admin['image']->temporaryUrl() }}" alt=""
                                                    class="mx-auto rounded-circle d-block" width="200px"
                                                    height="200">
                                            </div>
                                        @else
                                            <div class="product-img rounded">
                                                <img src="{{ image('uploads/admins', $adm->image) }}" alt=""
                                                    class="mx-auto rounded-circle d-block" width="200px"
                                                    height="200">
                                            </div>
                                        @endif
                                        <div wire:loading wire:target="admin.image"
                                            class="spinner-container position-absolute bottom-50 start-50 translate-middle"
                                            style="z-index: 1;">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="choices-single-default" class="form-label">Image</label>
                                            <input wire:model.defer="admin.image" type="file" class="form-control"
                                                name="image" id="image">
                                            @error('admin.image')
                                                <span class="ms-2 text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="first_name">First Name</label>
                                    <input wire:model.defer="admin.first_name" id="first_name" name="first_name"
                                        placeholder="Enter first name" type="text" class="form-control">
                                    @error('admin.first_name')
                                        <span class="ms-2 text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="last_name">Last Name</label>
                                    <input wire:model.defer="admin.last_name" id="last_name" name="last_name"
                                        placeholder="Enter last name" type="text" class="form-control">
                                    @error('admin.last_name')
                                        <span class="ms-2 text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>


                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label" for="email">Email</label>
                                    <input wire:model.defer="admin.email" id="email" name="email"
                                        placeholder="Enter email" type="text" class="form-control">
                                    @error('admin.email')
                                        <span class="ms-2 text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label" for="password">Password</label>
                                    <input wire:model.defer="admin.password" id="password" name="password"
                                        placeholder="Enter password Number" type="password" class="form-control">
                                    @error('admin.password')
                                        <span class="ms-2 text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    <div class="row mb-4">
        <div class="col text-end">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-danger"> <i class="bx bx-x me-1"></i> Cancel
            </a>
            <a href="#" wire:click.prevent="updateAdmin" class="btn btn-success"> <i
                    class=" bx bx-file me-1"></i>
                Save </a>
        </div> <!-- end col -->
    </div> <!-- end row-->
</div>
