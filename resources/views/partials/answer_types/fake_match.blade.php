<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <h4 class="m-0 fs-5 d-flex">
            <span class="text-danger fs-5 me-3 fw-bold"><i class="fa-solid fa-arrow-right"></i></span>
            <div>
                {!! $question->question !!}
            </div>
        </h4>
        @if ($question->image)
            <div class="col-12 mb-2 text-center">
                <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
            </div>
        @endif
        <input type="text" name="answer[{{ $question->id }}][type]" id="" value="match" hidden>

    </div>

    <div class="row row-cols-2 row-cols-md-2 row-cols-lg-4 mb-3">
        @if(isset($question->options['choice']))
            @foreach ($question->options['choice'] as $choice)
                @if ($choice)
                    <div class="col mb-2">
                        <div class="match-word-box h-100 mt-4 border border-primary text-center p-1 text-black-50"
                            id="{{ $question->id }}_{{ $choice }}" data-choice="{{ $choice }}"
                            data-input="answer[{{ $question->id }}][answer]">
                            @if ($question->isImgs)
                                <img src="{{ asset('assets/images/' . trim($choice)) }}" alt="" class="img-fluid"
                                    style="height: 100px;">
                            @else
                                <h6 class="mt-5">{{ $choice }}</h6>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    <div class="row">
        @if(isset($question->options['answer']))
            @foreach ($question->options['answer'] as $answer)
                @if ($answer)
                    <div class="col-6 col-md-3 option-con mt-4">
                        <div class="match-option-box w-100 h-100 draggable border text-center aligh-items-center" draggable="true"
                            id="{{ $question->id }}_{{ $answer }}" data-answer="{{ $answer }}">
                            @if ($question->isImgs)
                                <img src="{{ asset('assets/images/' . trim($answer)) }}" alt="" class="img-fluid"
                                    style="height: 100px;">
                            @else
                                <h6 class="mt-5">{{ $answer }}</h6>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
    <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>
    <input id="question_{{ $question->id }}_answer" type="hidden" hidden name="answer[{{ $question->id }}][answer]"
        value='{}'>
</div>