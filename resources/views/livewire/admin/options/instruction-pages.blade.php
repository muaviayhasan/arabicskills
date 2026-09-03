<div>
    <div class="row justify-content-center align-items-center g-2">
        <div class="col-md-12">
            <div class="mb-3">
                <h6 class="form-label" for="dashboard_instructions">Student Dashboard Instructions Page</h6>
                <div wire:ignore>
                    <textarea id="dashboard_instructions_summernote" class="form-control summernote" rows="8" style="height: 250px">{{ $inputs['dashboard_instructions'] ?? '' }}</textarea>
                    <input type="hidden" wire:model="inputs.dashboard_instructions" id="dashboard_instructions">
                </div>
                @error('inputs.dashboard_instructions')
                    <span class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <h6 class="form-label" for="reading_exam_instructions">Reading Exam Instructions Page</h6>
                <div wire:ignore>
                    <textarea id="reading_exam_instructions_summernote" class="form-control summernote" rows="8"
                        style="height: 250px">{{ $inputs['reading_exam_instructions'] ?? '' }}</textarea>
                    <input type="hidden" wire:model="inputs.reading_exam_instructions" id="reading_exam_instructions">
                </div>
                @error('inputs.reading_exam_instructions')
                    <span class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <h6 class="form-label" for="listening_exam_instructions">Listening Exam Instructions Page</h6>
                <div wire:ignore>
                    <textarea id="listening_exam_instructions_summernote" class="form-control summernote" rows="8"
                        style="height: 250px">{{ $inputs['listening_exam_instructions'] ?? '' }}</textarea>
                    <input type="hidden" wire:model="inputs.listening_exam_instructions"
                        id="listening_exam_instructions">
                </div>
                @error('inputs.listening_exam_instructions')
                    <span class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <h6 class="form-label" for="writing_exam_instructions">Writing Exam Instructions Page</h6>
                <div wire:ignore>
                    <textarea id="writing_exam_instructions_summernote" class="form-control summernote" rows="8"
                        style="height: 250px">{{ $inputs['writing_exam_instructions'] ?? '' }}</textarea>
                    <input type="hidden" wire:model="inputs.writing_exam_instructions" id="writing_exam_instructions">
                </div>
                @error('inputs.writing_exam_instructions')
                    <span class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <h6 class="form-label" for="speaking_exam_instructions">Speaking Exam Instructions Page</h6>
                <div wire:ignore>
                    <textarea id="speaking_exam_instructions_summernote" class="form-control summernote" rows="8"
                        style="height: 250px">{{ $inputs['speaking_exam_instructions'] ?? '' }}</textarea>
                    <input type="hidden" wire:model="inputs.speaking_exam_instructions"
                        id="speaking_exam_instructions">
                </div>
                @error('inputs.speaking_exam_instructions')
                    <span class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="mb-3">
                <h6 class="form-label" for="sentences_structures_exam_instructions">Sentences Structures Exam Instructions Page</h6>
                <div wire:ignore>
                    <textarea id="sentences_structures_exam_instructions_summernote" class="form-control summernote" rows="8"
                        style="height: 250px">{{ $inputs['sentences_structures_exam_instructions'] ?? '' }}</textarea>
                    <input type="hidden" wire:model="inputs.sentences_structures_exam_instructions"
                        id="sentences_structures_exam_instructions">
                </div>
                @error('inputs.sentences_structures_exam_instructions')
                    <span class="text-danger ms-2">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="modal-footer">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-danger">Cancel</a>
                <button type="button" wire:click.prevent="save" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
