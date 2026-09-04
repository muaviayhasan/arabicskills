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
        <i class="bx bx-chevron-{{ $direction === 'asc' ? 'up' : 'down' }} align-middle"></i>
    @else
        <i class="bx bx-chevron-down align-middle text-muted opacity-50"></i>
    @endif
</th>
