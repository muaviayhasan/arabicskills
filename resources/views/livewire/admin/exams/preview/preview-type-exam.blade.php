<div>
    <x-preview-header
        :title="$title"
        :exam="$exam"
        :allocatedTime="$allocatedTime"
        :backUrl="route('admin.exam-preview-types', ['exam' => $exam->id])"
        backLabel="Back to Types"
    />

    <section class="quiz">
        <div class="container">
            <div class="row">
                <div class="alert alert-info mb-4">
                    <i class="fa-solid fa-circle-info"></i> You can interact with all question types to preview the exam experience. Answers will not be saved.
                </div>

                <form id="preview_exam" wire:ignore>
                    <div class="quiz-items preview-exam-content">
                    @switch($type)
                        @case('reading')
                            @foreach ($activities as $i => $activity)
                                <h4 class="activity-paragraph question mt-4 d-flex" tabindex="0">
                                    <span class="text-danger fs-5 me-3 fw-bold text-nowrap">Q-{{ $i + 1 }}</span>
                                    <div>{!! $activity->activity !!}</div>
                                </h4>
                                @if ($activity->image)
                                    <div class="text-center">
                                        <img src="{{ read_image($activity->image) }}" alt="{{ $activity->title }}"
                                            style="max-width: 100%;" height="300px">
                                    </div>
                                @endif
                                <div class="ms-5">
                                    @forelse ($activity->Question as $qi => $question)
                                        @include("partials.answer_types.{$question->type}", [
                                            'question' => $question,
                                            'index' => $qi,
                                        ])
                                    @empty
                                        <div class="text-center">
                                            <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                                        </div>
                                    @endforelse
                                </div>
                            @endforeach
                            @break

                        @case('listening')
                            <h4 class="question my-4" tabindex="0">
                                Listen the audio carefully and answer the questions given below.
                            </h4>
                            @foreach ($activities as $i => $activity)
                                <div class="activity-paragraph d-flex gap-3 mt-3">
                                    <span class="text-danger fs-5 me-3 fw-bold text-nowrap">
                                        Q{{ $i + 1 }} <i class="fa-solid fa-arrow-right"></i>
                                    </span>
                                    <audio controls controlsList="nodownload noplaybackrate">
                                        <source src="{{ read_image($activity->activity) }}" type="audio/mpeg">
                                    </audio>
                                </div>
                                @if ($activity->image)
                                    <div class="text-center">
                                        <img src="{{ read_image($activity->image) }}" alt="{{ $activity->title }}"
                                            style="max-width: 100%;" height="300px">
                                    </div>
                                @endif
                                <div class="ms-5">
                                    @forelse ($activity->Question as $qi => $question)
                                        @include("partials.answer_types.{$question->type}", [
                                            'question' => $question,
                                            'index' => $qi,
                                        ])
                                    @empty
                                        <div class="text-center">
                                            <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                                        </div>
                                    @endforelse
                                </div>
                            @endforeach
                            @break

                        @case('writing')
                            @forelse ($activities as $i => $activity)
                                <div class="ms-5 mt-3">
                                    <h4 class="activity-paragraph question my-4 d-flex" tabindex="0">
                                        <span class="text-danger fs-5 me-3 fw-bold">Q-{{ $i + 1 }}</span>
                                        <div>{!! $activity->activity !!}</div>
                                    </h4>
                                    @if ($activity->image)
                                        <div class="text-center">
                                            <img src="{{ read_image($activity->image) }}" alt="{{ $activity->title }}"
                                                style="max-width: 100%;" height="300px">
                                        </div>
                                    @endif
                                    @php $j = 0; @endphp
                                    @foreach ($activity->Question as $question)
                                        @php $j = $j + 1; @endphp
                                        @if ($question->type == 'typing' || $question->type == 'writing')
                                            @include('partials.answer_types.writing-answer', [
                                                'question' => $question,
                                                'index' => $j,
                                            ])
                                        @else
                                            @include("partials.answer_types.{$question->type}", [
                                                'question' => $question,
                                                'index' => $j,
                                            ])
                                        @endif
                                    @endforeach
                                </div>
                            @empty
                                <div class="text-center">
                                    <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                                </div>
                            @endforelse
                            @break

                        @case('speaking')
                            @forelse ($activities as $i => $activity)
                                <div class="mt-3">
                                    <h4 class="activity-paragraph question my-4 d-flex" tabindex="0">
                                        <span class="text-danger fs-5 me-3 fw-bold">Q-{{ $i + 1 }}</span>
                                        <div>{!! $activity->activity !!}</div>
                                    </h4>
                                    @if ($activity->image)
                                        <div class="text-center">
                                            <img src="{{ read_image($activity->image) }}" alt="{{ $activity->title }}"
                                                style="max-width: 100%;" height="300px">
                                        </div>
                                    @endif
                                    @php $j = 0; @endphp
                                    @foreach ($activity->Question as $question)
                                        @php $j = $j + 1; @endphp
                                        @include('partials.answer_types.speaking-answer', [
                                            'question' => $question,
                                            'index' => $j,
                                        ])
                                    @endforeach
                                </div>
                            @empty
                                <div class="text-center">
                                    <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                                </div>
                            @endforelse
                            @break

                        @case('sentences_structures')
                            @foreach ($activities as $i => $activity)
                                <h4 class="activity-paragraph question mt-4 d-flex" tabindex="0">
                                    <span class="text-danger fs-5 me-3 fw-bold text-nowrap">Q-{{ $i + 1 }}</span>
                                    <div>{!! $activity->activity !!}</div>
                                </h4>
                                @if ($activity->image)
                                    <div class="text-center">
                                        <img src="{{ read_image($activity->image) }}" alt="{{ $activity->title }}"
                                            style="max-width: 100%;" height="300px">
                                    </div>
                                @endif
                                <div class="ms-5">
                                    @forelse ($activity->Question as $qi => $question)
                                        @include("partials.answer_types.{$question->type}", [
                                            'question' => $question,
                                            'index' => $qi,
                                        ])
                                    @empty
                                        <div class="text-center">
                                            <h3 class="text-danger fs-5 me-3 fw-bold">No Question found.</h3>
                                        </div>
                                    @endforelse
                                </div>
                            @endforeach
                            @break
                    @endswitch
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        (function () {
            function addRemoveButtonToWord(word, pool, dropzone, input) {
                if (word.querySelector('.remove-word-btn')) {
                    return;
                }

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'remove-word-btn';
                btn.innerHTML = '<i class="fa-solid fa-times"></i>';
                btn.title = 'Remove';

                btn.addEventListener('click', e => {
                    e.stopPropagation();
                    btn.remove();
                    word.classList.remove('in-dropzone');
                    pool.appendChild(word);
                    updateRearrangeAnswer(dropzone, input);
                });

                word.appendChild(btn);
            }

            function updateRearrangeAnswer(dropzone, input) {
                const words = Array.from(dropzone.querySelectorAll('.word'))
                    .map(w => {
                        const textNode = Array.from(w.childNodes).find(node => node.nodeType === Node.TEXT_NODE);
                        return textNode ? textNode.textContent.trim() : '';
                    });

                input.value = JSON.stringify(words);

                const placeholder = dropzone.querySelector('.dropzone-placeholder');
                if (placeholder) {
                    placeholder.style.display = words.length > 0 ? 'none' : 'block';
                }
            }

            function initPreviewQuizInteractions() {
                if (window.__previewQuizBound || typeof jQuery === 'undefined') {
                    return;
                }

                window.__previewQuizBound = true;

                jQuery(document)
                    .off('click.previewQuiz', '.preview-exam-content .clickable-match')
                    .on('click.previewQuiz', '.preview-exam-content .clickable-match', function (event) {
                        event.preventDefault();

                        const $matchOption = jQuery(this);
                        if ($matchOption.hasClass('matched')) {
                            return;
                        }

                        const $quizBox = $matchOption.closest('.quiz-box');
                        $quizBox.find('.clickable-match').removeClass('selected');
                        $matchOption.addClass('selected');
                    });

                jQuery(document)
                    .off('click.previewQuiz', '.preview-exam-content .match-word-box')
                    .on('click.previewQuiz', '.preview-exam-content .match-word-box', function (event) {
                        event.preventDefault();

                        const $matchBox = jQuery(this);
                        if ($matchBox.children('.tooltip').length > 0) {
                            return;
                        }

                        const $quizBox = $matchBox.closest('.quiz-box');
                        const $selectedOption = $quizBox.find('.clickable-match.selected');
                        if ($selectedOption.length === 0) {
                            $matchBox.addClass('pulse-warning');
                            setTimeout(() => $matchBox.removeClass('pulse-warning'), 500);
                            return;
                        }

                        if (typeof handleClickMatch === 'function') {
                            handleClickMatch($matchBox, $selectedOption);
                        }

                        $selectedOption.removeClass('selected');
                    });

                document.body.addEventListener('click', function (e) {
                    if (e.target.closest('.remove-word-btn')) {
                        return;
                    }

                    const word = e.target.closest('.preview-exam-content .clickable-word');
                    if (!word || word.classList.contains('in-dropzone')) {
                        return;
                    }

                    e.preventDefault();

                    const quizBox = word.closest('.quiz-box');
                    if (!quizBox) {
                        return;
                    }

                    const pool = quizBox.querySelector('.wordContainer');
                    const dropzone = quizBox.querySelector('.dropzone');
                    const input = quizBox.querySelector('input[type="hidden"][name*="[answer]"]');

                    if (!pool || !dropzone || !input) {
                        return;
                    }

                    if (word.getAttribute('data-question-id') !== dropzone.getAttribute('data-question-id')) {
                        return;
                    }

                    word.classList.add('in-dropzone');
                    addRemoveButtonToWord(word, pool, dropzone, input);
                    dropzone.appendChild(word);
                    updateRearrangeAnswer(dropzone, input);
                });
            }

            document.addEventListener('DOMContentLoaded', initPreviewQuizInteractions);
            document.addEventListener('livewire:init', initPreviewQuizInteractions);
        })();
    </script>
</div>
