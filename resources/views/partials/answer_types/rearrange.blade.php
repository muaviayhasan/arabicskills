<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <h4 class="m-0 fs-5">
            <span class="text-danger fs-5 me-3 fw-bold"><i class="fa-solid fa-arrow-right"></i></span>
            Click the words to arrange them in correct order.
        </h4>
        @if ($question->image)
            <div class="col-12 mb-2 text-center">
                <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
            </div>
        @endif
        <input type="text" name="answer[{{ $question->id }}][type]" id="" value="rearrange" hidden>

    </div>
    @php
        $raw = strip_tags($question->question);
        $words = explode(',', $raw);
        shuffle($words);

        // Arabic is read right to left, so the first word chosen belongs on the
        // right and each next one to its left. The activity's lang column says
        // "english" even on Arabic papers, so the direction is taken from the
        // words themselves rather than trusted from the record.
        $dir = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $raw)
            ? 'rtl'
            : 'ltr';
    @endphp
    <div class="row">
        <h5 class="wordContainer" dir="{{ $dir }}" data-input="question_{{ $question->id }}_answer"
            data-question-id="{{ $question->id }}">
            @foreach ($words as $w)
                <span class="word clickable-word" data-question-id="{{ $question->id }}">{{ $w }}</span>
            @endforeach
        </h5>

        <div id="dropzone_{{ $question->id }}" class="dropzone mt-3" dir="{{ $dir }}"
            data-question-id="{{ $question->id }}">
            <div class="dropzone-placeholder float-start">Selected words will appear here in order</div>
        </div>

        <input id="question_{{ $question->id }}_answer" type="hidden" name="answer[{{ $question->id }}][answer]"
            value="{}">
        <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>

    </div>
</div>
