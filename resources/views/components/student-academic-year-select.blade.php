@props([
    'startYear' => 2000,
    'endYear' => null,
])

@php
    $endYear = (int) ($endYear ?? date('Y'));
    $startYear = (int) $startYear;
@endphp

<select {{ $attributes->merge(['class' => 'form-select bg-light border-light rounded']) }}>
    <option value="">All Years</option>
    @for ($academicYear = $endYear; $academicYear >= $startYear; $academicYear--)
        <option value="{{ $academicYear }}">{{ $academicYear }}</option>
    @endfor
</select>
