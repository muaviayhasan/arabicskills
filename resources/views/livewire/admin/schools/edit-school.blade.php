<div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-between">
                    <h3>
                        Update School Profile In The System
                        <a href="{{ route('admin.schools') }}" class="btn btn-info float-end"><i
                                class='bx bx-arrow-back me-1'></i>Back</a>
                    </h3>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row">

                            <h5 class="font-size-14 mb-3">Enter relevent and accurate data in fields</h5>
                            <div class="col-12">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="name">School Name</h6>
                                    <input wire:model.defer="inputs.name" id="name" name="name"
                                        placeholder="Enter first name" type="text" class="form-control">
                                    @error('inputs.name')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="phone_number">Phone Number</h6>
                                    <input wire:model.defer="inputs.phone_number" id="phone_number" name="phone_number"
                                        placeholder="Enter phone number" type="text" class="form-control">
                                    @error('inputs.phone_number')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="email">Email</h6>
                                    <input wire:model.defer="inputs.email" id="email" name="email"
                                        placeholder="Enter email" type="email" class="form-control">
                                    @error('inputs.email')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="principal">Principal Name</h6>
                                    <input wire:model.defer="inputs.principal" id="principal" name="principal"
                                        placeholder="Enter principal name" type="text" class="form-control">
                                    @error('inputs.principal')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="school_type">School Type</h6>
                                    <select wire:model.defer="inputs.school_type" id="school_type" name="school_type"
                                        class="form-control">
                                        <option value="">Select school type</option>
                                        <option value="Private">Private</option>
                                        <option value="Public">Public</option>
                                    </select>
                                    @error('inputs.school_type')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="establishment_year">Establishment Year</h6>
                                    <input wire:model.defer="inputs.establishment_year" id="establishment_year"
                                        name="establishment_year" placeholder="Enter establishment year" type="number"
                                        class="form-control">
                                    @error('inputs.establishment_year')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="website">Website Link</h6>
                                    <input wire:model.defer="inputs.website" id="website" name="website"
                                        placeholder="Enter website link" type="text" class="form-control">
                                    @error('inputs.website')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="address">Address</h6>
                                    <textarea wire:model.defer="inputs.address" id="address" name="address" placeholder="Enter school address"
                                        class="form-control" rows="5"></textarea>
                                    @error('inputs.address')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if (isset($inputs['logo']) && !is_int($inputs['logo']))
                                    <div class="product-img rounded">
                                        <img src="{{ $inputs['logo']->temporaryUrl() }}" alt=""
                                            class="mx-auto rounded d-block" width="200px" height="80px">
                                    </div>
                                @else
                                    <div class="product-img rounded">
                                        <img src="{{ read_image($school->logo) }}" alt=""
                                            class="mx-auto rounded d-block" width="200px" height="80px">
                                    </div>
                                @endif
                                <div wire:loading wire:target="inputs.logo"
                                    class="spinner-container position-absolute bottom-50 start-50 translate-middle"
                                    style="z-index: 1;">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h6 class="ms-2" for="choices-single-default">Logo</h6>
                                    <input wire:model.defer="inputs.logo" type="file" class="form-control"
                                        name="logo" id="logo">
                                    @error('inputs.logo')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-3">
                                    <h6 class="ms-2" for="additional_notes">Additional Notes</h6>
                                    <textarea wire:model.defer="inputs.additional_notes" id="additional_notes" name="additional_notes"
                                        placeholder="Enter additional notes" class="form-control" rows="5"></textarea>
                                    @error('inputs.additional_notes')
                                        <span
                                            class="ms-2 text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
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
            <a href="{{ route('admin.schools') }}" class="btn btn-danger"> <i class="bx bx-x me-1"></i> Cancel
            </a>
            <a href="#" wire:click.prevent="updateSchool" class="btn btn-success"> <i
                    class=" bx bx-file me-1"></i>
                Save </a>
        </div> <!-- end col -->
    </div> <!-- end row-->
</div>
