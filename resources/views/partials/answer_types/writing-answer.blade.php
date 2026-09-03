<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <h4 class="m-0 ms-5 fs-5 d-flex">
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
        <input type="text" name="answer[{{ $question->id }}][type]" id="" value="typing" hidden>
        <div class="mb-4">
            <div class="question-container">
                <div class="select-opt mt-4">
                    <ul class="nav nav-pills mb-3 gap-3" id="pills-tab-{{ $question->id }}" role="tablist">
                        <li class="nav-item" role="presentation">


                            <button class="nav-link toogleMethod active" id="tab-{{ $question->id }}"
                                data-bs-toggle="pill" data-bs-target="#pills-Writing-{{ $question->id }}" type="button"
                                role="tab" aria-controls="pills-Writing-{{ $question->id }}" aria-selected="true"
                                data-index="{{ $question->id }}">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $question->id }}-Writing"
                                        id="Writing-{{ $question->id }}" checked>
                                    <label class="form-check-label" for="{{ $question->id }}-Writing">
                                        Hand Writing
                                    </label>
                                </div>
                            </button>
                        </li>
                        <li class="nav-item d-block" role="presentation">
                            <button class="nav-link toogleMethod" id="pills-Keyboard-tab-{{ $question->id }}"
                                data-bs-toggle="pill" data-bs-target="#pills-Keyboard-{{ $question->id }}"
                                type="button" role="tab" aria-controls="pills-Keyboard-{{ $question->id }}"
                                aria-selected="false" data-index="{{ $question->id }}">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="{{ $question->id }}-keyboard"
                                        id="Keyboard-{{ $question->id }}" checked>
                                    <label class="form-check-label" for="{{ $question->id }}-keyboard">
                                        <span class="d-none d-lg-block">Keyboard</span><span
                                            class="d-block d-lg-none">Write</span>
                                    </label>
                                </div>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="pills-tab-{{ $question->id }}-Content">
                        <div class="tab-pane fade show active" id="pills-Writing-{{ $question->id }}" role="tabpanel"
                            aria-labelledby="pills-Writing-tab-{{ $question->id }}" tabindex="0">
                            <div class="upload-img">
                                <input class="writing_file" type="file" id="fileInput_{{ $question->id }}"
                                    name="answer[{{ $question->id }}][answer][]"
                                    data-prev="imagePreview_{{ $question->id }}" multiple accept="image/*">
                                <div class="mt-4 d-flex flex-wrap align-items-center"
                                    id="imagePreview_{{ $question->id }}"></div>
                                <span class="text-danger"></span>
                            </div>

                        </div>
                        <div class="tab-pane fade" id="pills-Keyboard-{{ $question->id }}" role="tabpanel"
                            aria-labelledby="pills-Keyboard-tab-{{ $question->id }}" tabindex="0">
                            <div class="position-relative mb-4">

                                <div class="form-area">
                                    <textarea name="answer[{{ $question->id }}][answer]" class="form-control anstextarea no-copy-paste"
                                        placeholder="Leave a comment here" style="height: 100px" id="textInput_{{ $question->id }}"></textarea>


                                    <button type="button" class="d-none d-lg-block showKeyboardBtn"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>
    </div>
</div>
