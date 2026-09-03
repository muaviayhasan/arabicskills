<div class="row justify-content-center align-items-center g-2 mb-5">
    @php
        $lang = $lang ?? 'english';
        $defaultQuestion = \App\Support\QuestionTypeDefaults::question('match_words', $lang);
    @endphp
    <h5 class="my-2"><i class='bx bxs-right-arrow-square'></i>Matching words type question for assessment
        <button type="button" class="remove_question btn btn-danger float-end"
            @if (isset($question)) data-questionid="{{ $question->id }}" @endif>
            <i class="bx bxs-trash"></i>
        </button>
    </h5>
    <div class="col-md-8">
        <div class="row justify-content-center align-items-center g-2">
            <div class="col-md-12">
                <h6>Question</h6>
                <input type="hidden" name="question[{{ $questionIndex }}][type]" value="match_words">

                @if (isset($question))
                    <input type="hidden" name="question[{{ $questionIndex }}][id]" value="{{ $question->id }}">
                    @php
                        $options = $question->options;
                    @endphp
                @endif

                <textarea wire:ignore name="question[{{ $questionIndex }}][question]" class="form-control summernote_bank"
                    rows="3" placeholder="match images with words">{!! $question->question ?? $defaultQuestion !!}</textarea>

                <span id="question.{{ $questionIndex }}.options.question" class="text-danger"></span>

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

    <div class="col-12">
        <h4 class="my-2 ">Question Words</h4>

        <div class="row justify-content-center align-items-center g-2">
            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][activity][0]" type="text" class="form-control"
                    placeholder="type question word" value="{{ $options['activity'][0] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.activity.0" class="text-danger"></span>
            </div>

            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][activity][1]" type="text" class="form-control"
                    placeholder="type question word" value="{{ $options['activity'][1] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.activity.1" class="text-danger"></span>
            </div>

            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][activity][2]" type="text" class="form-control"
                    placeholder="type question word" value="{{ $options['activity'][2] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.activity.2" class="text-danger"></span>
            </div>

            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][activity][3]" type="text" class="form-control"
                    placeholder="type question word" value="{{ $options['activity'][3] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.activity.3" class="text-danger"></span>
            </div>

            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][activity][4]" type="text" class="form-control"
                    placeholder="type question word" value="{{ $options['activity'][4] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.activity.4" class="text-danger"></span>
            </div>

        </div>
    </div>

    <span id="question.{{ $questionIndex }}.options.activity" class="text-danger"></span>





    <div class="col-12">
        <h4 class="my-2 ">Option Words(Incorrect Order)</h4>
        <div class="row justify-content-center align-items-center g-2">
            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][answer][0]" type="text" class="form-control"
                    placeholder="type answer option" value="{{ $options['answer'][0] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.answer.0" class="text-danger"></span>
            </div>
            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][answer][1]" type="text" class="form-control"
                    placeholder="type answer option" value="{{ $options['answer'][1] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.answer.1" class="text-danger"></span>
            </div>
            <div class="col-md-2 mb-3">

                <input name="question[{{ $questionIndex }}][options][answer][2]" type="text" class="form-control"
                    placeholder="type answer option" value="{{ $options['answer'][2] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.answer.2" class="text-danger"></span>
            </div>
            <div class="col-md-2 mb-3">
                <input name="question[{{ $questionIndex }}][options][answer][3]" type="text" class="form-control"
                    placeholder="type answer option" value="{{ $options['answer'][3] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.answer.e" class="text-danger"></span>
            </div>
            <div class="col-md-2 mb-3">

                <input name="question[{{ $questionIndex }}][options][answer][4]" type="text" class="form-control"
                    placeholder="type answer option" value="{{ $options['answer'][4] ?? '' }}">

                <span id="question.{{ $questionIndex }}.options.answer.4" class="text-danger"></span>
            </div>
        </div>
        <span id="question.{{ $questionIndex }}.options.answer" class="text-danger"></span>
    </div>

    <div class="col-12">
        <small>arrange correct answers according to question words like answer for 1st word then answer for second word
            and so on. To avoid any typing mismatch, copy the correct answers directly from the options input
            box.</small>
        <select class="select_answers w-100" name="question[{{ $questionIndex }}][correct_answer][]" multiple>

            @if (isset($question->correct_answer))
                @foreach (unserialize($question->correct_answer) as $ans)
                    <option value="{{ $ans }}" selected>{{ $ans }}</option>
                @endforeach
            @endif
        </select>
    </div>
</div>
