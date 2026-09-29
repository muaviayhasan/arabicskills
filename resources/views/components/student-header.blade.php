@props([
    'title' => null,
    'remainingSeconds' => null,
])

{{--
    The header on every student screen.

    Titles are passed as "Reading Comprehension <span>فهم المقروء</span>":
    the text is the English, the span is the Arabic. Both halves are set as
    one lockup, which is why the title is echoed unescaped.

    Pass remainingSeconds to get the exam countdown. Its markup is driven by
    take_exam.js, which reads #timer, reserves space with #timer-placeholder
    and pins the timer to the body on scroll, so those ids are load-bearing.
--}}
<section class="page-title ast-header">
    <div class="container">

        <div class="ast-header-utility">
            <span class="ast-header-mark">
                <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}"
                    alt="{{ config('options.web_name') }}">
            </span>

            <a href="{{ route('student.logout') }}" class="btn ast-header-logout">
                Logout
            </a>
        </div>

        <div class="ast-header-main">
            <div class="ast-header-heading">
                @if ($title)
                    <h1 class="ast-header-title">{!! $title !!}</h1>
                @endif

                <div class="ast-header-identity">
                    {{-- Large enough to catch a wrong login before an exam starts. --}}
                    <p class="ast-header-name">{{ auth()->user()?->name }}</p>

                    <div class="ast-header-meta">
                        <span><span class="label">Class</span> {{ auth()->user()?->Grade?->name ?? '—' }}</span>
                        <span><span class="label">Division</span> {{ auth()->user()?->Section?->name ?? '—' }}</span>
                        <span class="ast-header-level">
                            <i class="fa-solid fa-circle-check"></i>
                            {{ auth()->user()?->assignedLevel?->name ?? 'Level not set' }}
                        </span>
                    </div>
                </div>
            </div>

            @if (! is_null($remainingSeconds))
                <div class="ast-header-timer">
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
            @endif
        </div>

    </div>
</section>
