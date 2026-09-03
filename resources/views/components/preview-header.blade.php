@props(['title', 'exam' => null, 'backUrl', 'backLabel' => 'Back', 'allocatedTime' => null])

<section class="page-title preview-page-title py-4">
    <div class="container position-relative" style="z-index: 1;">
        <div class="d-lg-flex justify-content-between align-items-center gap-3">
            <div class="text-center text-lg-start">
                <span class="badge bg-warning text-dark mb-2">Preview Mode</span>
                <h2 class="fw-bold text-white mb-2">{!! $title !!}</h2>
                @if ($exam)
                    <p class="text-white mb-0">
                        <strong>School:</strong> {{ $exam->School->name ?? 'N/A' }}
                        <span class="mx-2">|</span>
                        <strong>Grade:</strong> {{ $exam->Grade->name ?? 'N/A' }}
                        <span class="mx-2">|</span>
                        <strong>Level:</strong> {{ \App\Support\ExamLevelHelper::levelNamesLabel($exam->level_ids ?? []) }}
                        @if ($exam->term)
                            <span class="mx-2">|</span>
                            <strong>Term:</strong> {{ $exam->term }}
                        @endif
                    </p>
                @endif
                @if ($allocatedTime)
                    <p class="text-white mb-0 mt-1">
                        <strong>Allocated Time:</strong> {{ $allocatedTime }}
                    </p>
                @endif
            </div>
            <div class="text-center">
                <a href="{{ $backUrl }}" class="btn btn-light position-relative" style="z-index: 2;">
                    <i class="fa-solid fa-arrow-left"></i> {{ $backLabel }}
                </a>
            </div>
        </div>
    </div>
</section>

<style>
    .preview-page-title::before {
        pointer-events: none;
    }
</style>
