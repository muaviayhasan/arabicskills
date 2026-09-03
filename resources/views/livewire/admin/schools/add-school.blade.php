<div>
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header align-items-between">
                    <h3>
                        Register New School In The System
                        <a href="{{ route('admin.schools') }}" class="btn btn-info float-end"><i
                                class='bx bx-arrow-back me-1'></i>Back</a>
                    </h3>
                </div>
                <div class="card-body">
                    <form>
                        <div class="row">

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
                                    <h6 class="ms-2" for="establishment_year">Registration Year</h6>
                                    <input wire:model.defer="inputs.establishment_year" id="establishment_year"
                                        name="establishment_year" placeholder="Enter establishment year" type="number"
                                        class="form-control">
                                    @error('inputs.establishment_year')
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
            <a href="#" wire:click.prevent="addSchool" class="btn btn-success"> <i class=" bx bx-file me-1"></i>
                Save </a>
        </div> <!-- end col -->
    </div> <!-- end row-->
</div>
