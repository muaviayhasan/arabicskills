<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <h4 class="m-0 fs-5 d-flex">
            <div>
                {!! $question->question !!}
            </div>
        </h4>
        <input type="text" name="answer[{{ $question->id }}][type]" id="" value="match_words" hidden>
        @if ($question->image)
        <div class="col-12 mb-2 text-center">
            <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
        </div>
        @endif
    </div>
    <div class="row row-cols-2 row-cols-md-2 row-cols-lg-4">
        @foreach ($question->options['activity'] as $activity)
        @if ($activity)
        <div class="col">
            <div class="match-word-box mt-4 border text-center p-3 text-black-50"
                id="{{ $question->id }}_{{ $activity }}" data-question-id="{{ $question->id }}" data-activity="{{ $activity }}"
                data-input="answer[{{ $question->id }}][answer]">
                <h6>{{ $activity }}</h6>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    <div class="row">
        <div class="option-con mt-4">
            @foreach ($question->options['answer'] as $answer)
            @if ($answer)
            <div class="clickable-match border p-3" id="{{ $question->id }}_{{ $answer }}"
                data-question-id="{{ $question->id }}" data-answer="{{ $answer }}">{{ $answer }}</div>
            @endif
            @endforeach

        </div>
    </div>
    <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>
    <input id="question_{{ $question->id }}_answer" type="hidden" hidden name="answer[{{ $question->id }}][answer]"
        value='{}'>
</div>