@props([
    'startYear' => 2000,
    'endYear' => null,
])

@php
    $endYear = (int) ($endYear ?? date('Y'));
    $startYear = (int) $startYear;

    // Styled for a toolbar by default. A caller that passes its own class —
    // a labelled filter card, say — gets that instead, not both.
    $default = $attributes->has('class') ? '' : 'form-select bg-light border-light rounded';
@endphp

<select {{ $attributes->merge(['class' => $default]) }}>
    <option value="">All Years</option>
    @for ($academicYear = $endYear; $academicYear >= $startYear; $academicYear--)
        <option value="{{ $academicYear }}">{{ $academicYear }}</option>
    @endfor
</select>
