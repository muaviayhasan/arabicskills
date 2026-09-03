<div class="row justify-content-center align-items-center g-2 mb-5">
    @php
        $lang = $lang ?? 'english';
        $defaultQuestion = \App\Support\QuestionTypeDefaults::question('MCQs', $lang);
    @endphp
    <h5 class="my-2">
        <i class="bx bxs-right-arrow-square"></i> MCQ type question for
        assessment
        <button type="button" class="remove_question btn btn-danger float-end"
            @if (isset($question)) data-questionid="{{ $question->id }}" @endif>
            <i class="bx bxs-trash"></i>
        </button>
    </h5>
    <div class="col-md-8">
        <div class="row justify-content-center align-items-center g-2">
            <div class="col-12">
                <h6>Question</h6>

                <input type="hidden" name="question[{{ $questionIndex }}][type]" value="MCQs">

                @if (isset($question))
                    @php
                        $options = $question->options;
                    @endphp
                    <input type="hidden" name="question[{{ $questionIndex }}][id]" value="{{ $question->id }}">
                @endif
                <textarea wire:ignore name="question[{{ $questionIndex }}][question]"
                    class="form-control question_input summernote_bank" rows="2" placeholder="Type mcq question">{{ $question->question ?? $defaultQuestion }}</textarea>
                <span id="question.{{ $questionIndex }}.question" class="text-danger"></span>
            </div>
            <div class="col-md-6">
                <h6>Option A</h6>
                <input type="text" name="question[{{ $questionIndex }}][options][a]" class="form-control"
                    placeholder="Enter option A" value="{{ $options['a'] ?? '' }}" />
            </div>

            <div class="col-md-6">
                <h6>Option B</h6>
                <input type="text" name="question[{{ $questionIndex }}][options][b]" class="form-control"
                    placeholder="Enter option B" value="{{ $options['b'] ?? '' }}" />
            </div>

            <div class="col-md-6">
                <h6>Option C</h6>
                <input type="text" name="question[{{ $questionIndex }}][options][c]" class="form-control"
                    placeholder="Enter option C" value="{{ $options['c'] ?? '' }}" />
            </div>

            <div class="col-md-6">
                <h6>Option D</h6>
                <input type="text" name="question[{{ $questionIndex }}][options][d]" class="form-control"
                    placeholder="Enter option D" value="{{ $options['d'] ?? '' }}" />
            </div>
        </div>
    </div>


    <div class="col-md-4">

        <div class="product-img q-img rounded position-relative w-75 mx-auto">
            <img id="question_{{ $questionIndex }}_image" src="{{ read_image($question->image ?? null) }}"
                alt="" class="mx-auto rounded d-block" width="100%" height="150px">
            <button type="button" class="img_button btn position-absolute top-50 start-50 translate-middle w-100 h-100"
                data-input="question[{{ $questionIndex }}][image]"><i class="bx bx-camera text-dark fs-1"></i></button>
        </div>
        <input hidden name="question[{{ $questionIndex }}][image]" type="file" class="form-control imageInput"
            placeholder="Enter image" data-tag="question_{{ $questionIndex }}_image">

        <span id="question.{{ $questionIndex }}.image" class="text-danger"></span>


        <h6 class="mt-5">Correct Option</h6>
        <select name="question[{{ $questionIndex }}][correct_answer]" class="form-control">
            <option value="">Select Correct Option</option>
            <option value="a" @if (isset($question) && $question->correct_answer == 'a') selected @endif>a</option>
            <option value="b" @if (isset($question) && $question->correct_answer == 'b') selected @endif>b</option>
            <option value="c" @if (isset($question) && $question->correct_answer == 'c') selected @endif>c</option>
            <option value="d" @if (isset($question) && $question->correct_answer == 'd') selected @endif>d</option>
        </select>

    </div>
</div>
