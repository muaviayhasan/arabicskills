<div>
    <form id="addForm" action="">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading">Important Information</h4>
                    <p>All image either assessment image or questions images are optional. If image is not important do
                        not
                        include.</p>

                </div>
                <div class="row justify-content-center align-items-center g-2">
                    <div class="col-md-8">
                        <div class="row justify-content-center align-items-center g-2">

                            <select hidden id="activity_type" name="activity[type]" class="form-control">
                                <option value="">Select Assessment Type</option>
                                <option {{ $type == 'reading' ? 'selected' : '' }} value="reading">
                                    Reading</option>
                                <option {{ $type == 'listening' ? 'selected' : '' }} value="listening">Listening
                                </option>
                                <option {{ $type == 'writing' ? 'selected' : '' }} value="writing">Writing
                                </option>
                                <option {{ $type == 'speaking' ? 'selected' : '' }} value="speaking">Speaking
                                </option>
                                <option {{ $type == 'sentences_structures' ? 'selected' : '' }}
                                    value="sentences_structures">Sentences Structures
                                </option>
                            </select>
                            <div class="col-md-12 mb-3">
                                <h6>Assessment Title(for admin remembrance)</h6>
                                <input type="text" name="activity[title]" class="form-control"
                                    placeholder="Give any title to use when making exam">
                                <span id="activity.title" class="text-danger"></span>

                            </div>
                            <div class="col-md-6 mb-3">
                                <h6>Year / Grade</h6>
                                <select name="activity[grade_id]" class="form-control">
                                    <option value="">Select Grade</option>
                                    @foreach ($grades as $grade)
                                        <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                    @endforeach
                                </select>
                                <span id="activity.grade_id" class="text-danger"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6>Exam Level</h6>
                                <select name="activity[level_id]" class="form-control">
                                    <option value="">Select Level</option>
                                    @foreach ($levels as $level)
                                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                                    @endforeach
                                </select>
                                <span id="activity.level_id" class="text-danger"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6>Term</h6>
                                <select name="activity[term]" class="form-control">
                                    <option value="">Select Term</option>
                                    @foreach (unserialize(config('options.terms')) as $term)
                                        <option value="{{ $term }}">{{ $term }}</option>
                                    @endforeach
                                </select>

                                <span id="activity.term" class="text-danger"></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6>Language</h6>
                                <select id="activity_lang" name="activity[lang]" class="form-control">
                                    <option value="english" selected>English</option>
                                    <option value="arabic">Arabic</option>
                                </select>
                                <span id="activity.lang" class="text-danger"></span>
                            </div>

                            <div class="col-md-12">
                                <div id="activityDiv" wire:ignore class="mb-5">

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 position-relative">
                        <div class="product-img q-img rounded position-relative w-100 h-100 mx-auto">
                            <img id="activity_image" src="{{ asset('assets/images/no-image.png') }}" alt=""
                                class="mx-auto rounded d-block" width="100%" height="200px">
                            <button type="button"
                                class="img_button btn position-absolute top-50 start-50 translate-middle w-100 h-100"
                                data-input="activity[image]"><i class="bx bx-camera text-dark fs-1"></i></button>

                        </div>


                        <input hidden name="activity[image]" type="file" class="form-control imageInput"
                            placeholder="Enter image" data-tag="activity_image">

                        <span id="activity.image" class="text-danger"></span>

                    </div>


                    {{-- div where new questions appends --}}
                    <div class="col-12 mb-3" wire:ignore id="questions_div">

                    </div>

                    <span id="question" class="text-danger"></span>

                    <div class="col-12" id="question_type_div">
                        <h5 id="question_h5" class="mb-3 d-none">Create any of below type Question related to Assessment
                        </h5>
                        <div class="row justify-content-center align-items-center g-2">
                            <div class="col-md-8 mb-3">
                                <select id="question_type" class="form-control">
                                    <option value="" selected>Select a type for new question</option>
                                    <option value="MCQs">MCQ</option>
                                    <option value="true-false">True And False</option>
                                    <option value="blanks">Fill In The Blanks</option>
                                    <option value="typing">Typing</option>
                                    <option value="rearrange">Rearrange sentence</option>
                                    <option value="match">Match</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <p class="ms-3 text-danger d-none" id="question_type_error">Please select question type
                                    first
                                </p>
                                <button type="button" id="add_new_question" class="btn btn-warning form-control"
                                    data-index="0"><i class='bx bx-plus-circle'></i>
                                    Add</button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col text-end">

                <a href="{{ route('admin.question-banks') }}" class="btn btn-danger"> <i class="bx bx-x me-1"></i>
                    Cancel
                </a>
                <button type="submit" class="btn btn-success"> <i class=" bx bx-file me-1"></i>
                    Save </button>
            </div> <!-- end col -->
        </div> <!-- end row-->
    </form>
</div>
