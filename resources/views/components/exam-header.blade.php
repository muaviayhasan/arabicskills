@props(['title', 'remainingSeconds'])
<section class="page-title py-5">
    <style>
        .timer {
            font-size: 20px;
            font-weight: 700;
        }

        .timer.fixed {
            position: fixed !important;
            top: 16px;
            right: 16px;
            z-index: 2147483647;
            background: #fff;
            padding: 6px 12px;
            border-radius: 8px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
        }
    </style>
    <div class="container">
        <div class="row">
            <div class="d-lg-flex justify-content-between align-items-center">
                <div class="text-center">
                    <h3 class="fw-bold mb-3 text-white">
                        {{ auth()->user()?->name }}</h3>

                    <h5 class="text-white"><b>Class :</b>
                        {{ auth()->user()?->Grade?->name }}
                        <b class="ms-4">Division :</b> {{ auth()->user()?->Section?->name }}
                        <b class="ms-4">Level :</b> {{ auth()->user()?->assignedLevel?->name ?? 'N/A' }}
                    </h5>
                </div>
                <div class="text-center mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3 text-white">{!! $title !!}</h2>

                </div>
                <div>
                    <div wire:ignore.self id="header-counter" class="countdown">
                        <div class="position-relative">
                            <svg class="progress-ring" width="120" height="120">
                                <circle class="progress-ring__circle" stroke="#ffffff" stroke-width="8"
                                    fill="transparent" r="56" cx="60" cy="60" />
                            </svg>
                            <div class="countdown-text countdown-wrapper">
                                <div id="timer-placeholder"></div>

                                <div id="timer" data-remaining="{{ $remainingSeconds }}" class="timer">
                                    --:--:--
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <div wire:ignore.self id="scrollDiv" class="position-fixed"
        style="display: none; z-index: 1050; top: 35px; right: 80px;">
        <div class="countdown">
            <div class="timer">
                <svg class="progress-ring" width="120" height="120">
                    <circle class="progress-ring__circle" stroke="#000053" stroke-width="8" fill="transparent" r="56"
                        cx="60" cy="60" />
                </svg>
                <div class="countdown-text text-dark">{{ $timer }}</div>
            </div>
        </div>
    </div> --}}
</section>
