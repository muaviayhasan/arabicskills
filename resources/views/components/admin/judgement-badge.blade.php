@props(['judgement' => null, 'small' => false])

@if ($judgement)
    <span @class([
        'badge fw-normal',
        'fs-6' => !$small,
        'bg-danger' => $judgement === \App\Support\MarkRanges::BELOW,
        'bg-warning text-dark' => $judgement === \App\Support\MarkRanges::IN_LINE,
        'bg-success' => $judgement === \App\Support\MarkRanges::ABOVE,
    ])>{{ $judgement }}</span>
@else
    <span class="text-muted">—</span>
@endif
