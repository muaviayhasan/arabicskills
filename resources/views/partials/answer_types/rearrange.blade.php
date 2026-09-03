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
        $words = explode(',', strip_tags($question->question));
        shuffle($words);
    @endphp
    <div class="row">
        <h5 class="wordContainer" data-input="question_{{ $question->id }}_answer" data-question-id="{{ $question->id }}">
            @foreach ($words as $w)
                <span class="word clickable-word" data-question-id="{{ $question->id }}">{{ $w }}</span>
            @endforeach
        </h5>

        <div id="dropzone_{{ $question->id }}" class="dropzone mt-3" data-question-id="{{ $question->id }}">
            <div class="dropzone-placeholder float-start">Selected words will appear here in order</div>
        </div>

        <input id="question_{{ $question->id }}_answer" type="hidden" name="answer[{{ $question->id }}][answer]"
            value="{}">
        <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>

    </div>
</div>
