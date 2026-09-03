$(document).ready(function () {


    var activityType = $("#activity_type").val();
    if (activityType) {
        if (activityType == "writing") {
            $("#question_type").html(`<option selected>Select a type for new question</option>
                            <option value="MCQs">MCQ</option>
                            <option value="true-false">True And False</option>
                            <option value="typing">Typing</option>
                            `);
        } else if (activityType == "speaking") {
            $("#question_type").html(`
                            <option value="typing" selected>Speaking</option>
                            `);
        }

        $.ajax({
            url: '/adminino/set-activity-type-input',
            type: 'GET',
            data: { activity_type: activityType },
            success: function (response) {
                $('#activityDiv').html(response.html);
                setupSummernote();
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });

    }

    function setupSummernote() {
        $('.summernote_bank').each(function () {
            var $summernote = $(this);
            // var editorId = $summernote.attr('id');
            // var inpName = $summernote.attr('data-input');
            if ($summernote)
                $summernote.summernote({
                    toolbar: [
                        // Define the toolbar groups and buttons you want to include
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['view', ['fullscreen', 'codeview', 'help']],
                    ]
                })
        });
    }
    setupSelect2();
    setupMatchType();
    function setupSelect2() {
        $('.select_answers').each(function () {
            var select2 = $(this);
            $(select2).select2({
                width: 'resolve',
                tags: true,
                multiple: true,
                placeholder: "Type and press enter to create",
                tokenSeprators: [',', ' ']
            });
        });
    }
    $(document).on("click", "#add_new_question", function () {
        var questionType = $("#question_type").val();
        var questionTypeError = $("#question_type_error");
        var question_index = parseInt($(this).attr("data-index"));

        if (questionType) {
            Swal.fire({
                html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,

            });

            questionTypeError.addClass("d-none");

            var new_question_index = question_index + 1;
            $(this).attr("data-index", new_question_index);

            var newDivId = generateRandomString(8);
            var newDiv = $("<div>").addClass("question_div").addClass("border-top").addClass("border-primary").addClass("p-2").attr("id", newDivId);

            $.ajax({
                url: '/adminino/set-question-type-input',
                type: 'GET',
                data: { questionType: questionType, question_index: question_index, lang: $("#activity_lang").val() || 'english' },
                success: function (response) {

                    $("#questions_div").append(newDiv);

                    $('#' + newDivId).html(response.html);
                    swal.close();
                    setupSummernote();
                    setupSelect2();
                    setupMatchType();
                },
                error: function (xhr, status, error) {
                    console.error('Error:', xhr);
                    swal.close();
                }
            });
        }
        else {
            questionTypeError.removeClass("d-none");
        }
    });

    $("#question_type").on("change", function () {
        var questionType = $(this).val();
        var questionTypeError = $("#question_type_error");

        if (questionType) {
            questionTypeError.addClass("d-none");
        }
    });
    // Generate a random string of length 8
    function generateRandomString(length) {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';

        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * characters.length);
            result += characters.charAt(randomIndex);
        }

        return result;
    }

    $(document).on("click", ".remove_question", function (e) {
        e.preventDefault();

        const btn = $(this);
        const question = btn.closest('.row');

        if (!question.length) return;

        const questionId = btn.data('questionid');
console.log(questionId);

        Swal.fire({
            title: 'Are you sure?',
            text: 'This question will be removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {

                question.remove();

                if (questionId) {
                    Livewire.dispatch('questionDelete', { id: questionId });
                    return
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Question removed',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        });
    });



    $("#addForm").submit(function (e) {
        e.preventDefault();

        var formData = new FormData(document.getElementById("addForm"));

        Swal.fire({
            html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,

        });

        fetch("/adminino/question-banks/question/add", {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    Swal.close();

                    if (response.headers.get('content-type').includes('application/json')) {
                        return response.json().then(data => Promise.reject(data));
                    } else {
                        return response.text().then(errorMessage => Promise.reject(new Error(`HTTP error! : ${errorMessage}`)));
                    }
                }
                return response.json();
            })
            .then(data => {
                fetchConfirm({
                    'title': data.message,
                    "confirm": "/adminino/question-banks/question/add/" + activityType,
                    "cancel": "/adminino/question-banks"
                })
            })
            .catch(error => {
                Swal.close();

                if (error instanceof Error) {

                    console.error('Network Error:', error.message);
                } else {

                    console.error('Response Error:', error);

                    if (error.errors) {
                        Object.keys(error.errors).forEach(function (key) {
                            var firstError = error.errors[key];
                            console.log("Field:", key);
                            console.log("Error:", firstError[0]);

                            $("#" + key.replace(/\./g, "\\.")).html(firstError[0].replace(/(activity\.|question\.\d+\.)/g, " "));
                        });
                    }
                }
            });
    });


    $(document).on("change", ".imageInput", function () {

        const input = this;
        const pr = $(this).data("tag");

        const preview = $("#" + pr);
        const wrapper = preview.closest(".product-img");
        const cameraBtn = wrapper.find(".img_button");

        const defaultSrc = preview.data("default") || preview.attr("src");
        preview.data("default", defaultSrc);

        const errorMessage = $(this).closest("div").find(".text-danger");
        const file = input.files[0];

        // remove old remove button
        wrapper.find(".remove-btn").remove();

        if (!file) return;

        const reader = new FileReader();

        reader.onload = function (e) {
            preview.attr("src", e.target.result);
            errorMessage.text("");

            // hide camera button
            cameraBtn.addClass("d-none");

            // create remove button
            const removeBtn = $(`
            <button type="button"
                class="remove-btn btn position-absolute top-50 start-50 translate-middle bg-transparent p-0"
                style="z-index: 10;">
                <i class="bx bx-x-circle text-danger fs-1"></i>
            </button>
        `);

            wrapper.append(removeBtn);

            // remove handler
            removeBtn.on("click", function (e) {
                e.stopPropagation();

                preview.attr("src", defaultSrc);
                input.value = "";
                errorMessage.text("");

                removeBtn.remove();
                cameraBtn.removeClass("d-none"); // show camera again
            });
        };

        // 🔹 AUDIO
        if (pr === "audioFile") {
            if (file.type.match(/^audio\/(mpeg|mp3|m4a)$/)) {
                reader.readAsDataURL(file);
            } else {
                errorMessage.text("Please select a valid audio file (MP3 or M4A).");
                input.value = "";
            }
            return;
        }

        // 🔹 IMAGE
        if (file.type.match(/^image\/(png|jpeg)$/)) {
            reader.readAsDataURL(file);
        } else {
            errorMessage.text("Please select a valid image file (PNG or JPEG).");
            input.value = "";
        }
    });

    // if (typeof setProperties !== 'undefined') {
    // if (setProperties.image) {
    //     $("#activity_image").attr("src", setProperties.image);
    // }

    // if (setProperties.type == "listening") {
    //     $("#audioFile").attr('src', setProperties.activity);
    // }

    // setQuestions();


    $("#editForm").submit(function (e) {
        e.preventDefault();

        var formData = new FormData(document.getElementById("editForm"));

        Swal.fire({
            html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,

        });

        fetch("/adminino/question-banks/question/edit", {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    Swal.close();

                    if (response.headers.get('content-type').includes('application/json')) {
                        return response.json().then(data => Promise.reject(data));
                    } else {
                        return response.text().then(errorMessage => Promise.reject(new Error(`HTTP error! : ${errorMessage}`)));
                    }
                }
                return response.json();
            })
            .then(data => {
                fetchSuccess({
                    'title': data.message,
                    "url": "/adminino/question-banks"
                })
            })
            .catch(error => {
                Swal.close();

                if (error instanceof Error) {

                    console.error('Network Error:', error.message);
                } else {

                    console.error('Response Error:', error);

                    if (error.errors) {
                        Object.keys(error.errors).forEach(function (key) {
                            var firstError = error.errors[key];
                            console.log("Field:", key);
                            console.log("Error:", firstError[0]);

                            $("#" + key.replace(/\./g, "\\.")).html(firstError[0].replace(/(activity\.|question\.\d+\.)/g, " "));
                        });
                    }
                }
            });
    });
    // }

    $(document).on("click", ".img_button", function (e) {
        var inp = $(this).attr('data-input');
        // alert("input[" + inp+"]");
        $("input[name='" + inp + "']").click();
    })



    // Function to handle checkbox changes
    function handleCheckboxChange() {
        const checkboxes = $('.exam_activities');

        // Get all checked checkboxes
        const checkedCheckboxes = checkboxes.filter(':checked');

        const checkedValues = checkedCheckboxes.map(function () {
            return $(this).val();
        }).get();

        Livewire.dispatch('updateTabs', {
            valu: checkedValues
        });
    }

    $(document).on('change', '.exam_activities', handleCheckboxChange);
});


function setupMatchType(event) {
    document.querySelectorAll('.input-group-text').forEach(button => {
        button.removeEventListener('click', handleMatchType); // Remove any old listeners

    });

    document.querySelectorAll('.input-group-text').forEach(button => {
        button.addEventListener('click', handleMatchType); // Attach the new listener
    });
}


function handleMatchType(event) {
    const button = event.target;
    const input = button.parentElement.querySelector('input');

    if (input.type === 'text') {
        const newInput = document.createElement('input');
        newInput.type = 'file';
        newInput.className = input.className;
        newInput.name = input.name;


        button.parentElement.replaceChild(newInput, input);
        button.innerText = 'Text';
        newInput.click();
    } else {
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.className = input.className;
        newInput.placeholder = 'type or click image to upload image';
        newInput.name = input.name;

        button.parentElement.replaceChild(newInput, input);
        button.innerText = 'Image';
    }
}





