<div wire:ignore class="quiz-box py-4">
    <div class="row">
        <h4 class="m-0 fs-5 d-flex">
            <span class="text-danger fs-5 me-3 fw-bold"><i class="fa-solid fa-arrow-right"></i></span>
            <div>
                <p class="mb-0" dir="rtl" style="text-align: right;"><strong>حدّد العبارات الصحيحة (صح) والعبارات
                        الخاطئة (خط) بالملائمة.</strong></p>
                {!! $question->question !!}
            </div>
        </h4>
        <input type="text" name="answer[{{ $question->id }}][type]" id="" value="true-false" hidden>

        @if ($question->image)
            <div class="col-12 mb-2 text-center">
                <img src="{{ read_image($question->image) }}" class="quiz-img" style="max-width: 100%;" height="300px">
            </div>
        @endif
    </div>
    <div class="row">
        <div class="skills">
            <div class="row row-cols-1 row-cols-lg-2">
                <div class="col">
                    <div class="skill-box">
                        <label class="custom-radio">
                            <input type="radio" name="answer[{{ $question->id }}][answer]" value="F" />
                            <span class="radio-btn d-md-flex align-items-center">
                                <i class="fa-solid fa-check"></i>
                                <h5 class="my-0">False / خطأ </h5>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="col">
                    <div class="skill-box">
                        <label class="custom-radio">
                            <input type="radio" name="answer[{{ $question->id }}][answer]" value="T" />
                            <span class="radio-btn d-md-flex align-items-center">
                                <i class="fa-solid fa-check"></i>
                                <h5 class="my-0">True / صح</h5>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <span class="text-danger text-error" id="answer.{{ $question->id }}.answer"></span>
    </div>
</div>
