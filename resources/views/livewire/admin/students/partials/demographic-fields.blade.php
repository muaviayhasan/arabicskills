{{-- Gender, SEN, G&T and Citizen — shared by the Add and Edit student forms. --}}
<div class="col-md-6">
    <div class="mb-3">
        <h6>Gender</h6>
        <select wire:model.defer="inputs.gender" class="form-control">
            <option value="">Select Gender</option>
            @foreach (\App\Support\StudentDemographics::GENDERS as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        @error('inputs.gender')
            <span class="text-danger">{{ str_replace(['inputs.', 'field'], '', $message) }}</span>
        @enderror
    </div>
</div>

@foreach (\App\Support\StudentDemographics::FLAGS as $column => $label)
    <div class="col-md-6">
        <div class="mb-3">
            <h6>{{ $label }}</h6>
            <select wire:model.defer="inputs.{{ $column }}" class="form-control">
                <option value="">Select</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
            @error('inputs.' . $column)
                <span class="text-danger">{{ str_replace(['inputs.', 'field', '_'], ['', '', ' '], $message) }}</span>
            @enderror
        </div>
    </div>
@endforeach
