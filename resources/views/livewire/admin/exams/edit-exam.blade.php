<div>
    <div class="card">
        <form wire:submit.prevent="editExam">
            <div class="card-body">
                <div class="row justify-content-center align-items-center">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>School</h6>
                            <select wire:model.live="inputs.school_id" class="form-control">
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
                        <div class="mb-3">
                            <h6>Grade</h6>
                            <select wire:model="inputs.grade_id" class="form-control">
                                <option value="">Select Grade</option>
                                @foreach ($grades as $gd)
                                    <option value="{{ $gd->id }}">{{ $gd->name }}</option>
                                @endforeach
                            </select>
                            @error('inputs.grade_id')
                                <span
                                    class="text-danger">{{ str_replace(['inputs.', 'field', ' id'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    @include('livewire.admin.exams.partials.exam-levels-select2')

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Term</h6>
                            <select wire:model.defer="inputs.term" class="form-control">
                                <option value="">Select term of exam</option>
                                @foreach (unserialize(config('options.terms')) as $term)
                                    <option value="{{ $term }}">{{ $term }}</option>
                                @endforeach
                            </select>
                            @error('inputs.term')
                                <span
                                    class="text-danger">{{ str_replace(['inputs.', 'field', ' id'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Writing Quiz Time</h6>
                            <input wire:model.defer="inputs.writing_time" type="text" class="form-control"
                                placeholder="Time allocated for writing assessment format: HH:MM">
                            @error('inputs.writing_time')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Listening Quiz Time</h6>
                            <input wire:model.defer="inputs.listening_time" type="text" class="form-control"
                                placeholder="Time allocated for listening assessment format: HH:MM">
                            @error('inputs.listening_time')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Speaking Quiz Time</h6>
                            <input wire:model.defer="inputs.speaking_time" type="text" class="form-control"
                                placeholder="Time allocated for speaking assessment format: HH:MM">
                            @error('inputs.speaking_time')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Reading Quiz Time</h6>
                            <input wire:model.defer="inputs.reading_time" type="text" class="form-control"
                                placeholder="Time allocated for reading assessment format: HH:MM">
                            @error('inputs.reading_time')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <h6>Sentences Structures Quiz Time</h6>
                            <input wire:model.defer="inputs.sentences_structures_time" type="text"
                                class="form-control"
                                placeholder="Time allocated for sentences structures assessment format: HH:MM">
                            @error('inputs.sentences_structures_time')
                                <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('admin.exams') }}" class="btn btn-danger">Cancel</a>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
