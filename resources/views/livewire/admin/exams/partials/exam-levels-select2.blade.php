<div class="col-md-12 mb-3">
    <h6>Exam Levels</h6>
    <div wire:ignore>
        <select class="form-control exam-level-ids-select2" style="width: 100%" multiple="multiple">
            @foreach ($levels as $level)
                <option value="{{ $level->id }}"
                    @selected(in_array((int) $level->id, array_map('intval', $inputs['level_ids'] ?? []), true))>
                    {{ $level->name }}
                </option>
            @endforeach
        </select>
    </div>
    <small class="text-muted">Select one or more levels for this exam.</small>
    @error('inputs.level_ids')
        <span class="text-danger d-block">{{ str_replace(['inputs.', 'field', ' id'], '', $message) }}</span>
    @enderror
    @error('inputs.level_ids.*')
        <span class="text-danger d-block">{{ $message }}</span>
    @enderror
</div>
