<div>
    <div class="card">
        <form wire:submit.prevent="addStudent">
            <div class="card-body">
                <div class="row justify-content-center align-items-center">
                    <div class="col-md-6 mb-3">
                        <div class="mb-3">

                            <h6>Name</h6>
                            <input wire:model.defer="inputs.name" type="text" class="form-control"
                                placeholder="Enter name">
                            @error('inputs.name')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror

                        </div>
                        <div class="mb-3">


                            <div class="mb-3">
                                <h6>Registaration Number</h6>
                                <input wire:model.defer="inputs.registration" type="number" class="form-control"
                                    placeholder="Enter registration">
                                @error('inputs.registration')
                                    <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <h6>Academic Year</h6>
                                <input type="text" class="form-control" value="{{ current_academic_year() }}" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <h6>School</h6>
                            <select wire:model="inputs.school_id" class="form-control">
                                <option value="">Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}">{{ $school->name }}</option>
                                @endforeach
                            </select>
                            @error('inputs.school_id')
                                <span
                                    class="text-danger">{{ str_replace(['inputs.', 'field', ' id'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        @if (isset($inputs['image']) && !is_int($inputs['image']))
                            <div class="product-img rounded">
                                <img src="{{ $inputs['image']->temporaryUrl() }}" alt=""
                                    class="mx-auto rounded d-block" width="100%" height="150px">
                            </div>
                        @else
                            <div class="product-img rounded">
                                <img src="{{ asset('assets/images/no-image.png') }}" alt=""
                                    class="mx-auto rounded d-block" width="100%" height="150px">
                            </div>
                        @endif
                        <div wire:loading wire:target="inputs.image"
                            class="spinner-container position-absolute bottom-50 start-50 translate-middle"
                            style="z-index: 1;">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <h6>Image</h6>
                        <input wire:model.defer="inputs.image" id="image" type="file" class="form-control"
                            placeholder="Enter image">
                        @error('inputs.image')
                            <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Grade</h6>
                            <select wire:model="inputs.grade_id" class="form-control " wire:change="updateSections">
                                <option value="">Select Grade</option>

                                @foreach ($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach

                            </select>
                            @error('inputs.grade_id')
                                <span
                                    class="text-danger">{{ str_replace(['inputs.', 'field', ' id'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Section</h6>
                            <select wire:model.defer="inputs.section_id" class="form-control ">
                                <option value="">Select Section</option>
                                @if (isset($inputs['grade_id']))
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('inputs.section_id')
                                <span
                                    class="text-danger">{{ str_replace(['inputs.', 'field', ' id'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>


                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Level</h6>
                            <select wire:model.defer="inputs.level_id" class="form-control ">
                                <option value="">Select Level</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                @endforeach
                            </select>
                            @error('inputs.level_id')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label" for="password-input">Password</label>
                            <div class="position-relative auth-pass-inputgroup input-custom-icon">
                                <span class="bx bx-lock-alt"></span>
                                <input wire:model.defer="inputs.password" type="text" class="form-control"
                                    id="password-input" placeholder="Enter password">
                                <button wire:click.prevent="makePass" type="button"
                                    class="btn btn-link position-absolute h-100 end-0 top-0 text-secondary"
                                    id="password-addon">
                                    New<i class="mdi mdi-key font-size-18 text-muted"></i>
                                </button>
                            </div>
                            @error('inputs.password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Nationality</h6>
                            <input wire:model.defer="inputs.nationality" type="text" class="form-control"
                                placeholder="Enter nationality">
                            @error('inputs.nationality')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Category</h6>
                            <input wire:model.defer="inputs.category" type="text" class="form-control"
                                placeholder="Enter category">
                            @error('inputs.category')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    @include('livewire.admin.students.partials.demographic-fields')
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.students') }}" class="btn btn-danger">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
