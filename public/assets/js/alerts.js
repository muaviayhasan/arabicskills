// make table rows dragable
// $(document).ready(function () {
//     $(".dragable").sortable({
//         update: function (event, ui) {
//             // This function is called when a row is dragged and dropped
//             var sortedRowIds = $(this).sortable("toArray");
//             console.log("Sorted row IDs:", sortedRowIds);

//             Livewire.dispatch('sequenceUpdated', { sortedRowIds: sortedRowIds });
//         }
//     });
// });
// JavaScript to update the image source in the modal
var modalImage = document.getElementById('modalImage');
var modal = document.getElementById('imageModal');

// Event listener for the buttons
var buttons = document.querySelectorAll('.imgButtons');
buttons.forEach(function (button) {
    button.addEventListener('click', function () {
        var imageSrc = button.getAttribute('data-image');
        modalImage.src = imageSrc;
        // modal.classList.toggle("fade");
    });
});





// profile input invoke
// var readURL = function (input) {
//     if (input.files && input.files[0]) {
//         var reader = new FileReader();
//         reader.onload = function (e) {
//             $('.changeprofile').attr('src', e.target.result);
//             $('.changepro').attr('src', e.target.result);
//         }
//         reader.readAsDataURL(input.files[0]);
//     }

// }


// $("#image_select").on('change', function () {
//     // if() use file type check
//     readURL(this);
// });
// $("#upload_image").on('click', function () {
//     $("#image_select").click();
// });




// simple alert 
window.addEventListener('swal:alert', function (e) {
    Swal.close();
    swal.fire({
        icon: e.detail.icon,
        title: e.detail.title,
        text: e.detail.text,
        background: e.detail.bg ?? '#fff',
        color: e.detail.cl ?? '#000',
    }).then(() => {
        if (e.detail.url) {
            window.location.href = e.detail.url;
        }
        if (e.detail.modal) {
            $(e.detail.modal).modal('hide');
        }
    });
});

window.addEventListener('swal:blackAlert', function (e) {
    swal.fire({
        icon: e.detail.icon,
        title: e.detail.title,
        text: e.detail.text,
        background: '#000',
        color: 'white',
    }).then(() => {
        if (e.detail.url) {
            window.location.href = e.detail.url;
        }
        if (e.detail.modal) {
            $(e.detail.modal).modal('hide');
        }
    });
});

// branch updated
window.addEventListener('branchUpdated', function (e) {
    swal.fire(e.detail).then(() => {
        window.location.href = "/client/branches";
    });
});

// confirm alert to switch branch after creation
window.addEventListener('confirmSwitch', function (e) {
    Swal.fire({
        title: 'New branch added successfully',
        text: 'Switch to new branch?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed',
    }).then((result) => {
        if (result.isConfirmed) {

            window.location.href = "/client/switch_branch?branch_id=" + e.detail.id;
        }
    }).catch(err => {
        console.log(err);
    });
});

// simple toast message
var toastMixin = Swal.mixin({
    toast: true,
    icon: 'success',
    title: 'General Title',
    animation: false,
    position: 'top-right',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});
window.addEventListener('swal:toast', function (e) {
    if (e.detail.modal) {
        $(e.detail.modal).modal('hide');
    }
    toastMixin.fire({
        title: e.detail.title,
        icon: e.detail.icon,
    }).then(() => {
        if (e.detail.url) {
            window.location.href = e.detail.url;
        }
    });
});

// black toast message
var blackMixin = Swal.mixin({
    toast: true,
    icon: 'success',
    title: 'General Title',
    animation: false,
    position: 'top-right',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,

    background: '#000', // Set the background color here
    color: 'white', // Set the text color here

    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});
window.addEventListener('swal:blackToast', function (e) {
    if (e.detail.modal) {
        $(e.detail.modal).modal('hide');
    }
    blackMixin.fire({
        title: e.detail.title,
        icon: e.detail.icon,
    }).then(() => {
        if (e.detail.url) {
            window.location.href = e.detail.url;
        }
    });
});


// profile updated
window.addEventListener('profileUpdated', function (e) {
    swal.fire(e.detail).then(() => {
        window.location.href = "/client/profile";
    });
});


// confirm bulk archive alert
window.addEventListener('confirmBulkArchive', function (e) {
    Swal.fire({
        title: 'Archive Confirmation',
        text: e.detail.text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive all',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            Livewire.dispatch(e.detail.emitBack);

            Swal.fire({
                html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
            });
        }
    }).catch(err => {
        console.log(err);
    });
});

// confirm bulk restore alert
window.addEventListener('confirmBulkRestore', function (e) {
    Swal.fire({
        title: 'Restore Confirmation',
        text: e.detail.text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, restore all',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            Livewire.dispatch(e.detail.emitBack);

            Swal.fire({
                html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
            });
        }
    }).catch(err => {
        console.log(err);
    });
});

// confirm restore alert
window.addEventListener('confirmRestore', function (e) {
    Swal.fire({
        title: 'Restore',
        text: e.detail.text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Restore',
    }).then((result) => {
        if (result.isConfirmed) {
            Livewire.dispatch(e.detail.emitBack, { id: e.detail.id });

            Swal.fire({
                html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
            });
        }
    }).catch(err => {
        console.log(err);
    });
});

// cofirm delete alert 
window.addEventListener('confirmDelete', function (e) {
    Swal.fire({
        title: 'Warning',
        text: e.detail.text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Confirm',
    }).then((result) => {
        if (result.isConfirmed) {
            Livewire.dispatch(e.detail.emitBack, { id: e.detail.id });

            Swal.fire({
                html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">Please wait...</p>`,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,

            });

        }
    }).catch(err => {
        console.log(err);
    });
});

// goEither
window.addEventListener('goEither', function (e) {
    Swal.fire({
        title: e.detail.title,
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Proceed',
        cancelButtonText: 'Return To Exams',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = e.detail.confirm;
        }
        else {
            window.location.href = e.detail.cancel;
        }
    }).catch(err => {
        console.log(err);
    });
});



// fetchSuccess
function fetchSuccess(data) {
    Swal.close();
    swal.fire({
        'icon': 'success',
        'title': data.title
    }).then(() => {
        window.location.href = data.url;
    });
}

function fetchConfirm(data) {
    Swal.close();
    Swal.fire({
        title: 'Success',
        text: data.title,
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Proceed',
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = data.confirm;
        }
        else {
            window.location.href = data.cancel;
        }

    }).catch(err => {
        console.log(err);
    });
}