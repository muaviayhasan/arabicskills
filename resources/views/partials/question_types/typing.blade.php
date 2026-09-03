<div class="row justify-content-center align-items-center g-2 mb-5">
    @php
        $lang = $lang ?? 'english';
        $defaultQuestion = \App\Support\QuestionTypeDefaults::question('typing', $lang);
    @endphp
    <h5 class="my-2"><i class='bx bxs-right-arrow-square'></i> Typing question for assessment
        <button type="button" class="remove_question btn btn-danger float-end"
            @if (isset($question)) data-questionid="{{ $question->id }}" @endif>
            <i class="bx bxs-trash"></i>
        </button>
    </h5>
    <div class="col-md-8">
        <div class="row justify-content-center align-items-center g-2">
            <div class="col-12">
                <h6>Question</h6>
                @if (isset($question))
                    <input type="hidden" name="question[{{ $questionIndex }}][id]" value="{{ $question->id }}">
                @endif

                <input type="hidden" name="question[{{ $questionIndex }}][type]" value="typing">
                <textarea wire:ignore name="question[{{ $questionIndex }}][question]" class="form-control summernote_bank"
                    rows="3" placeholder="Please follow patern to add fill in the blank : Is this day beautiful?">{{ $question->question ?? $defaultQuestion }}</textarea>
                <span id="question.{{ $questionIndex }}.question" class="text-danger"></span>
            </div>
        </div>
    </div>


    <div class="col-md-4 position-relative">

        <div class="product-img q-img rounded position-relative w-75 mx-auto">
            <img id="question_{{ $questionIndex }}_image" src="{{ read_image($question->image ?? null) }}"
                alt="" class="mx-auto rounded d-block" width="100%" height="150px">
            <button type="button" class="img_button btn position-absolute top-50 start-50 translate-middle w-100 h-100"
                data-input="question[{{ $questionIndex }}][image]"><i class="bx bx-camera text-dark fs-1"></i></button>
        </div>
        <input hidden name="question[{{ $questionIndex }}][image]" type="file" class="form-control imageInput"
            placeholder="Enter image" data-tag="question_{{ $questionIndex }}_image">

        <span id="question.{{ $questionIndex }}.image" class="text-danger"></span>
    </div>
</div>
