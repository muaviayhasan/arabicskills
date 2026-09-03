<div class="product-img rounded">
    <img id="question_{{ $questionIndex }}_image" src="{{ asset('assets/images/no-image.png') }}" alt=""
        class="mx-auto rounded d-block" width="75%" height="100px">
</div>

<label for="" class="form-label">Image</label>
<input name="question[{{ $questionIndex }}][image]" data-tag="question_{{ $questionIndex }}_image" id="image"
    type="file" class="form-control imageInput" placeholder="Enter image">

<span id="question.{{ $questionIndex }}.image" class="text-danger"></span>
