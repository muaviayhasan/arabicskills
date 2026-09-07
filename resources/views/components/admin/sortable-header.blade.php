@props([
    'field',
    'label',
    'current' => '',
    'direction' => 'asc',
])

@php($active = $current === $field)

<th {{ $attributes->merge(['class' => 'user-select-none']) }}
    role="button"
    tabindex="0"
    wire:click="sortBy('{{ $field }}')"
    wire:keydown.enter="sortBy('{{ $field }}')"
    aria-sort="{{ $active ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    title="Sort by {{ strtolower($label) }}">
    {{ $label }}
    @if ($active)
        {{-- Sorted: a single chevron showing the active direction. --}}
        <i class="bx bx-chevron-{{ $direction === 'asc' ? 'up' : 'down' }} align-middle"></i>
    @else
        {{-- Unsorted: both chevrons, so the column reads as sortable. --}}
        <span class="d-inline-flex flex-column align-middle text-muted opacity-50"
            style="line-height: .38;">
            <i class="bx bx-chevron-up"></i>
            <i class="bx bx-chevron-down"></i>
        </span>
    @endif
</th>
