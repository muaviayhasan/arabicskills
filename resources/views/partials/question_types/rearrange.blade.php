<div class="row justify-content-center align-items-center g-2 mb-5">
    <h5 class="my-2"><i class='bx bxs-right-arrow-square'></i> Rearrangement question for assessment
        <button type="button" class="remove_question btn btn-danger float-end"
            @if (isset($question)) data-questionid="{{ $question->id }}" @endif>
            <i class="bx bxs-trash"></i>
        </button>
    </h5>
    <div class="col-md-8">
        <div class="row justify-content-center align-items-center g-2">
            <div class="col-12">
                <h6>Question(Type word by word in random order)</h6>
                <input type="hidden" name="question[{{ $questionIndex }}][type]" value="rearrange">

                @if (isset($question))
                    <input type="hidden" name="question[{{ $questionIndex }}][id]" value="{{ $question->id }}">
                    @php
                        $question->question = $question->question ? explode(',', $question->question) : [];
                    @endphp
                @endif

                <select class="select_answers w-100 form-control" wire:ignore
                    name="question[{{ $questionIndex }}][question][]" multiple>

                    @foreach ($question->question ?? [] as $q)
                        <option value="{{ $q }}" selected>{{ $q }}</option>
                    @endforeach

                </select>

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
