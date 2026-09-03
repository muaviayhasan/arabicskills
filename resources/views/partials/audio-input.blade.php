<div class="row justify-content-center align-items-center g-2">

    <div class="col-md-4">
        <h6>{{ $label }}</h6>
        <input type="file" name="activity[activity]" class="form-control imageInput" data-tag="audioFile">
        <span id="activity.activity" class="ms-2 text-danger"></span>

    </div>
    <div class="col-md-8">
        <audio controlsList="nodownload noplaybackrate" id="audioFile" controls>
            <source class="form-control" src="{{ read_image($src ?? 0) }}" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>
    </div>
</div>