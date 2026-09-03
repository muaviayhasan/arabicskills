<div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3>
                        Here are your settings You can update any time
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-info float-end"><i
                                class='bx bx-arrow-back me-1'></i>Back</a>
                    </h3>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row justify-content-center align-items-center">

                                    <div class="col-md-6">
                                        @if (isset($inputs['logo']) && !is_string($inputs['logo']) && $inputs['logo']->temporaryUrl())
                                            <div class="product-img">
                                                <img src="{{ $inputs['logo']->temporaryUrl() }}" alt=""
                                                    class="d-block mx-auto rounded" width="200px" height="100">
                                            </div>
                                        @else
                                            <div class="product-img">
                                                <img src="{{ image('uploads/logo', $old_image ?? 'logo.png') }}"
                                                    alt="no-image" class="d-block mx-auto rounded" width="200px"
                                                    height="100">
                                            </div>
                                        @endif
                                        <div class="mb-3">
                                            <h6 for="choices-single-default" class="form-label">Business Logo</h6>
                                            <input wire:model.defer="inputs.logo" type="file" class="form-control"
                                                name="logo" id="logo">
                                            @error('inputs.logo')
                                                <span
                                                    class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                            @enderror
                                        </div>

                                    </div>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 for="choices-single-default" class="form-label">Site Name</h6>
                                    <input wire:model.defer="inputs.web_name" placeholder="Enter business name"
                                        type="text" class="form-control">
                                    @error('inputs.web_name')
                                        <span
                                            class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror

                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 for="choices-single-default" class="form-label">Site Email</h6>
                                    <input wire:model.defer="inputs.web_email" placeholder="Enter business email"
                                        type="email" class="form-control">
                                    @error('inputs.web_email')
                                        <span
                                            class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="form-label">Current Academic Year</h6>
                                    <input wire:model.defer="inputs.current_academic_year" type="number"
                                        class="form-control" placeholder="e.g. 2026" min="2000" max="2100">
                                    <small class="text-muted">Used for new student year and username format (AST-Y{yy}-registration).</small>
                                    @error('inputs.current_academic_year')
                                        <span
                                            class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <div wire:ignore class="">
                                    <h6>Exam Sessions/Terms</h6>
                                    <select class="form-control terms-select2" style="width: 100%" multiple="multiple">
                                        @if (!is_string($inputs['terms']))
                                            @foreach ($inputs['terms'] as $trm)
                                                <option value="{{ $trm }}" selected>{{ $trm }}
                                                </option>
                                            @endforeach
                                        @endif

                                    </select>
                                </div>
                                @error('inputs.terms')
                                    <span
                                        class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                @enderror
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
            @if (getPermissions('options', 'edit'))
                <a href="#" wire:click.prevent="updateSettings" class="btn btn-success"> <i
                        class="bx bx-file me-1"></i>
                    Save </a>
            @endif
        </div> <!-- end col -->
    </div> <!-- end row-->
</div>
