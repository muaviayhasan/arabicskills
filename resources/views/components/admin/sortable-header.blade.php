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
        {{-- Sorted: a single caret showing the active direction. --}}
        <i class="bx bx-caret-{{ $direction === 'asc' ? 'up' : 'down' }} align-middle"></i>
    @else
        {{-- Unsorted: both carets, so the column reads as sortable. --}}
        <span class="d-inline-flex flex-column align-middle text-muted opacity-50"
            style="line-height: .45;">
            <i class="bx bx-caret-up"></i>
            <i class="bx bx-caret-down"></i>
        </span>
    @endif
</th>
