<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <div class="mb-4">
            @if ($question->type == 'rearrange')
            <h3>Rearrange the words to correct sentence order</h3>
            @endif
            <h4 class="m-0 ms-5 fs-5 d-flex">
                <span class="text-danger fs-5 me-3 fw-bold"><i class="fa-solid fa-arrow-right"></i></span>

                <div class="d-flex">
                    <h4> {!!str_replace(',',' ',$question->question) !!} </h4>
                    <strong>{{ $question->type == 'true-false' ? '(T/F)' : '' }}</strong>
                </div>
            </h4>
            @if ($question->image)
            <div class="col-12 mb-2 text-center">
                <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
            </div>
            @endif
        </div>
        @if ($question->type == 'MCQs')
        <div class="col-md-3">(a){{ $question->options['a']??'' }}</div>
        <div class="col-md-3">(b){{ $question->options['b']??'' }}</div>
        <div class="col-md-3">(c){{ $question->options['c']??'' }}</div>
        <div class="col-md-3">(d){{ $question->options['d']??'' }}</div>
        @endif
       
    </div>

    <div class="row">
        <div class="mt-4 d-flex gap-3 record-container" id="record-container{{ $question->id }}" data-index="{{ $question->id }}">
            <button type="button" id="toggleButton{{ $question->id }}" class="btn-cls py-2 px-4"><i
                    class="fa-solid fa-play"></i>
                Start Recording</button>
            <audio controlsList="nodownload noplaybackrate" class="audioElement" controls
                id="audioElement{{ $question->id }}"></audio>
            <button type="button" class="btn btn-danger" style="display: none;" id="audioButton{{ $question->id }}">Delete</button>
            <input wire:ignore hidden type=" text" name="answer[{{ $question->id }}][answer]">

            <input type="text" name="answer[{{ $question->id }}][type]" value="speaking" hidden>

        </div>
    </div>
</div>