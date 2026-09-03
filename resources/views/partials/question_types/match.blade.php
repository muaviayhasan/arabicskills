<div class="match-question-root">
    @php
        $lang = $lang ?? 'english';
        $defaultQuestion = \App\Support\QuestionTypeDefaults::question('match', $lang);
    @endphp
    <div class="row justify-content-center align-items-center g-2 mb-5">
        <h5 class="my-2">
            <i class='bx bxs-right-arrow-square'></i>
            Matching images type question for assessment

            <button type="button" class="remove_question btn btn-danger float-end"
                @if (isset($question)) data-questionid="{{ $question->id }}" @endif>
                <i class="bx bxs-trash"></i>
            </button>
        </h5>

        <div class="col-md-8">
            <div class="row justify-content-center align-items-center g-2">
                <div class="col-md-12">
                    <h6>Question</h6>

                    <input type="hidden" name="question[{{ $questionIndex }}][type]" value="match">

                    @if (isset($question))
                        <input type="hidden" name="question[{{ $questionIndex }}][id]" value="{{ $question->id }}">
                    @endif

                    <textarea wire:ignore name="question[{{ $questionIndex }}][question]" class="form-control summernote_bank"
                        rows="3" placeholder="match images with words">{{ $question->question ?? $defaultQuestion }}</textarea>

                    <span id="question.{{ $questionIndex }}.options.question" class="text-danger"></span>
                </div>
            </div>
        </div>

        <div class="col-md-4 position-relative">
            <div class="product-img q-img rounded position-relative w-75 mx-auto">
                <img id="question_{{ $questionIndex }}_image" src="{{ read_image($question->image ?? null) }}"
                    class="mx-auto rounded d-block" width="100%" height="150px">

                <button type="button"
                    class="img_button btn position-absolute top-50 start-50 translate-middle w-100 h-100"
                    data-input="question[{{ $questionIndex }}][image]">
                    <i class="bx bx-camera text-dark fs-1"></i>
                </button>
            </div>

            <input hidden name="question[{{ $questionIndex }}][image]" type="file" class="form-control imageInput"
                data-tag="question_{{ $questionIndex }}_image">

            <span id="question.{{ $questionIndex }}.image" class="text-danger"></span>
        </div>

        @php $options = $question->options ?? []; @endphp

        <div class="col-12">
            <h6 class="my-2">Matching Question Images</h6>

            <div class="row justify-content-center align-items-center g-2">
                @for ($i = 0; $i < 5; $i++)
                    @php
                        $options['choice'][$i] = $options['choice'][$i] ?? '';
                        $choice_is_image = check_is_image($options['choice'][$i]) ? true : false;
                    @endphp
                    <div class="col-md-6 mb-3">
                        <div class="border border-secondary rounded p-2">
                            @if ($choice_is_image)
                                <div class="product-img q-img rounded position-relative w-100 h-100 mx-auto">
                                    <img id="question_{{ $questionIndex }}_options_choice_{{ $i }}"
                                        src="{{ read_image($options['choice'][$i]) }}" class="mx-auto rounded d-block"
                                        width="50%" height="100px">
                                </div>
                            @endif

                            <div class="d-flex align-items-center gap-2">
                                <strong class="text-danger">{{ $i + 1 }}</strong>
                                <div class="input-group">
                                    <button type="button" class="input-group-text small">Image</button>
                                    <input type="text" class="form-control"
                                        name="question[{{ $questionIndex }}][options][choice][{{ $i }}]"
                                        value="{{ $choice_is_image ? '' : $options['choice'][$i] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

            <span id="question.{{ $questionIndex }}.options.choice" class="text-danger"></span>
        </div>

        <div class="col-12">
            <h6 class="my-2">Matching Answers (Random Order)</h6>

            <div class="row justify-content-center align-items-center g-2">
                @for ($i = 0; $i < 5; $i++)
                    @php
                        $options['answer'][$i] = $options['answer'][$i] ?? '';
                        $answer_is_image = check_is_image($options['answer'][$i]) ? true : false;
                    @endphp
                    <div class="col-md-6 mb-3">
                        <div class="border border-secondary rounded p-2">
                            @if ($answer_is_image)
                                <div class="product-img q-img rounded position-relative w-100 h-100 mx-auto">
                                    <img id="question_{{ $questionIndex }}_options_answer_{{ $i }}"
                                        src="{{ read_image($options['answer'][$i]) }}" class="mx-auto rounded d-block"
                                        width="50%" height="100px">
                                </div>
                            @endif

                            <div class="d-flex align-items-center gap-2">
                                <strong class="text-danger">{{ $i + 1 }}</strong>
                                <div class="input-group">
                                    <button type="button" class="input-group-text small">Image</button>
                                    <input type="text" class="form-control"
                                        name="question[{{ $questionIndex }}][options][answer][{{ $i }}]"
                                        value="{{ $answer_is_image ? '' : $options['answer'][$i] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

            <span id="question.{{ $questionIndex }}.options.answer" class="text-danger"></span>
        </div>
    </div>
</div>
