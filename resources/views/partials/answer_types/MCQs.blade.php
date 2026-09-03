<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <h4 class="fs-5 d-flex m-0">
            <span class="text-danger fs-5 fw-bold me-3"><i class="fa-solid fa-arrow-right"></i></span>
            <div>
                <p class="mb-0" dir="rtl" style="text-align: right;"><strong>اختر الإجابة الصحيحة</strong></p>
                {!! $question->question !!}
            </div>
        </h4>
        @if ($question->image)
        <div class="col-12 mb-2 text-center">
            <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
        </div>
        @endif
        <input type="text" name="answer[{{ $question->id }}][type]" id="" value="MCQs" hidden>
    </div>

    <div class="row">
        <div class="skills">
            <div class="row row-cols-1 row-cols-lg-2">
                @if (!empty($question->options['a']))
                <div class="col">
                    <div class="skill-box">
                        <label class="custom-radio">
                            <input type="radio" name="answer[{{ $question->id }}][answer]" value="a" />
                            <span class="radio-btn d-md-flex align-items-center">
                                <i class="fa-solid fa-check"></i>
                                <h5 class="my-0">{{ $question->options['a'] }}</h5>
                            </span>
                        </label>
                    </div>
                </div>
                @endif
                @if (!empty($question->options['b']))
                <div class="col">
                    <div class="skill-box">
                        <label class="custom-radio">
                            <input type="radio" name="answer[{{ $question->id }}][answer]" value="b" />
                            <span class="radio-btn d-md-flex align-items-center">
                                <i class="fa-solid fa-check"></i>
                                <h5 class="my-0">{{ $question->options['b'] }}</h5>
                            </span>
                        </label>
                    </div>
                </div>
                @endif
                @if (!empty($question->options['c']))
                <div class="col">
                    <div class="skill-box">
                        <label class="custom-radio">
                            <input type="radio" name="answer[{{ $question->id }}][answer]" value="c" />
                            <span class="radio-btn d-md-flex align-items-center">
                                <i class="fa-solid fa-check"></i>
                                <h5 class="my-0">{{ $question->options['c'] }}</h5>
                            </span>
                        </label>
                    </div>
                </div>
                @endif
                @if (!empty($question->options['d']))
                <div class="col">
                    <div class="skill-box">
                        <label class="custom-radio">
                            <input type="radio" name="answer[{{ $question->id }}][answer]" value="d" />
                            <span class="radio-btn d-md-flex align-items-center">
                                <i class="fa-solid fa-check"></i>
                                <h5 class="my-0">{{ $question->options['d'] }}</h5>
                            </span>
                        </label>
                    </div>
                </div>
                @endif
            </div>
        </div>
        <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>
    </div>
</div>