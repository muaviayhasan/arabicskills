document.addEventListener('DOMContentLoaded', function () {
    const timer = document.getElementById('timer');
    const placeholder = document.getElementById('timer-placeholder');
    if (!timer || !placeholder) return;

    let remaining = parseInt(timer.dataset.remaining, 10);
    const originalParent = timer.parentNode;
    const originalNext = timer.nextSibling;
    const triggerOffset = timer.getBoundingClientRect().top + window.scrollY;

    function format(seconds) {
        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        return `${h}:${m}:${s}`;
    }

    timer.textContent = format(remaining);

    const interval = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(interval);
            timer.textContent = "00:00:00";
            if (!isSubmitting) Livewire.dispatch('expireExam');
            return;
        }
        timer.textContent = format(remaining);
    }, 1000);

    function pinToBody() {
        if (!timer.classList.contains('fixed')) {
            placeholder.style.height = timer.offsetHeight + 'px';
            document.body.appendChild(timer);
            timer.classList.add('fixed');
        }
    }

    function restorePosition() {
        if (timer.classList.contains('fixed')) {
            timer.classList.remove('fixed');
            placeholder.style.height = '0px';

            if (originalNext) {
                originalParent.insertBefore(timer, originalNext);
            } else {
                originalParent.appendChild(timer);
            }
        }
    }

    window.addEventListener('scroll', () => {
        if (window.scrollY > triggerOffset) {
            pinToBody();
        } else {
            restorePosition();
        }
    });
});


window.addEventListener('form_save', function (e) {
    Swal.fire({
        html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;">
                    </div>
                    <p class="mt-2">${e.detail.title}</p>`,
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: false,

    });
    isExpiring = true;
    $(e.detail.formId).submit();
});

var isSubmitting = false;
var isExpiring = false;


function confirmAndSubmit({ formId, url, onSuccess }) {

    if (isSubmitting) return;

    const doSubmit = () => {
        if (isSubmitting) return;

        isSubmitting = true;

        const form = document.getElementById(formId);
        const formData = new FormData(form);

        $(".text-error").html('');

        Swal.fire({
            html: `<div class="spinner-border text-primary" role="status" style="width: 4rem; height: 4rem;"></div>
                   <p class="mt-2">Submitting exam...</p>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,
        });

        fetch(url, {
            method: "POST",
            body: formData
        })
            .then(response => {
                if (!response.ok) {
                    Swal.close();
                    if (response.headers.get('content-type')?.includes('application/json')) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.text().then(msg =>
                        Promise.reject(new Error(msg))
                    );
                }
                return response.json();
            })
            .then(data => {
                Swal.close();
                onSuccess(data);
            })
            .catch(error => {
                Swal.close();
                isSubmitting = false;

                console.error("Submission Error:", error);

                if (error.errors) {
                    Object.keys(error.errors).forEach(key => {
                        $("#" + key.replace(/\./g, "\\."))
                            .html(error.errors[key][0]);
                    });
                    Swal.fire('Validation Error', 'Please check your answers.', 'error');
                } else {
                    Swal.fire('Error', error.message || 'An unexpected error occurred during submission.', 'error');
                }
            });
    };

    // ✅ Expiry → auto submit (NO confirmation)
    if (isExpiring) {
        doSubmit();
        return;
    }

    // ✅ Manual submit → confirmation (with unattempted question count)
    const counts = countUnattemptedQuestions(formId);

    let title = 'Proceed to submit?';
    let html  = 'Once you submit, you will not be able to return.';
    let icon  = 'question';

    if (counts.total > 0 && counts.unattempted > 0) {
        title = 'You have unattempted questions';
        const word = counts.unattempted === 1 ? 'question' : 'questions';
        html =
            `<p class="mb-2"><strong>${counts.unattempted}</strong> out of <strong>${counts.total}</strong> ${word} ` +
            `${counts.unattempted === 1 ? 'is' : 'are'} still unattempted.</p>` +
            `<p class="mb-0">If you still want to submit, click <strong>Yes, submit</strong>. ` +
            `Otherwise click <strong>No, attempt them</strong> and finish the remaining ${word}.</p>`;
        icon = 'warning';
    }

    Swal.fire({
        title: title,
        html: html,
        icon: icon,
        showCancelButton: true,
        confirmButtonText: 'Yes, submit',
        cancelButtonText: counts.unattempted > 0 ? 'No, attempt them' : 'No, stay here',
        allowOutsideClick: false,
        allowEscapeKey: true,
    }).then(result => {
        if (result.isConfirmed) doSubmit();
    });
}

/**
 * Count unattempted vs total questions in an exam form.
 * A question is identified by `name="answer[<id>][answer]"` (one or more inputs).
 * Returns { total, attempted, unattempted }.
 */
function countUnattemptedQuestions(formId) {
    const form = document.getElementById(formId);
    if (!form) return { total: 0, attempted: 0, unattempted: 0 };

    const inputs = form.querySelectorAll('[name^="answer["][name*="][answer]"]');
    const groups = {};

    inputs.forEach(el => {
        const m = el.name.match(/^answer\[(\d+)\]\[answer\]/);
        if (!m) return;
        const qid = m[1];
        if (!groups[qid]) groups[qid] = [];
        groups[qid].push(el);
    });

    let total = 0;
    let attempted = 0;

    Object.keys(groups).forEach(qid => {
        total++;
        const els = groups[qid];
        let hasAnswer = false;

        for (const el of els) {
            const tag  = el.tagName.toLowerCase();
            const type = (el.type || '').toLowerCase();

            if (tag === 'input' && (type === 'radio' || type === 'checkbox')) {
                if (el.checked) { hasAnswer = true; break; }
                continue;
            }

            if (tag === 'input' && type === 'file') {
                if (el.files && el.files.length > 0) { hasAnswer = true; break; }
                continue;
            }

            // textarea, text, hidden, etc.
            const v = (el.value || '').trim();
            if (v && v !== '{}' && v !== '[]') { hasAnswer = true; break; }
        }

        if (hasAnswer) attempted++;
    });

    return { total: total, attempted: attempted, unattempted: total - attempted };
}



$(document).ready(function () {


    // mange sentences_structures and listening exam 

    $("#sentences_structures_exam").submit(function (e) {
        e.preventDefault();

        confirmAndSubmit({
            formId: "sentences_structures_exam",
            url: "/student/exams/sentences-structures-exam",
            onSuccess: (data) => {
                fetchSuccess({
                    title: data.message,
                    url: "/student/exams"
                });
            }
        });
    });
    $("#reading_exam").submit(function (e) {
        e.preventDefault();

        confirmAndSubmit({
            formId: "reading_exam",
            url: "/student/exams/reading-exam",
            onSuccess: (data) => {
                fetchSuccess({
                    title: data.message,
                    url: "/student/exams"
                });
            }
        });
    });
    $("#listening_exam").submit(function (e) {
        e.preventDefault();

        confirmAndSubmit({
            formId: "listening_exam",
            url: "/student/exams/listening-exam",
            onSuccess: (data) => {
                fetchSuccess({
                    title: data.message,
                    url: "/student/exams"
                });
            }
        });
    });
    $("#writing_exam").submit(function (e) {
        e.preventDefault();

        confirmAndSubmit({
            formId: "writing_exam",
            url: "/student/exams/writing-exam",
            onSuccess: (data) => {
                fetchSuccess({
                    title: data.message,
                    url: "/student/exams"
                });
            }
        });
    });
    $("#speaking_exam").submit(function (e) {
        e.preventDefault();

        confirmAndSubmit({
            formId: "speaking_exam",
            url: "/student/exams/speaking-exam",
            onSuccess: (data) => {
                fetchSuccess({
                    title: data.message,
                    url: "/student/exams"
                });
            }
        });
    });

    $("#device_test_form").submit(function (e) {
        e.preventDefault();

        // Update device info right before submit
        const deviceInfo = {
            browser: navigator.userAgent,
            os: navigator.platform,
            screen_resolution: `${screen.width}x${screen.height}`,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            device_type: /Mobi|Android/i.test(navigator.userAgent) ? 'Mobile' : 'Desktop',
            touch_support: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
            cookies_enabled: navigator.cookieEnabled,
            microphone_available: false, // Default to false
        };
        
        // Try to check microphone status quickly if possible, otherwise use default
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then((stream) => {
                    deviceInfo.microphone_available = true;
                    stream.getTracks().forEach(track => track.stop());
                    $("#device_info_input").val(JSON.stringify(deviceInfo));
                    submitDeviceTest();
                })
                .catch(() => {
                    deviceInfo.microphone_available = false;
                    $("#device_info_input").val(JSON.stringify(deviceInfo));
                    submitDeviceTest();
                });
        } else {
            $("#device_info_input").val(JSON.stringify(deviceInfo));
            submitDeviceTest();
        }

        function submitDeviceTest() {
            confirmAndSubmit({
                formId: "device_test_form",
                url: "/student/device-test/submit",
                onSuccess: (data) => {
                    fetchSuccess({
                        title: data.message,
                        url: data.redirect
                    });
                }
            });
        }
    });

});

function fetchSuccess(data) {
    Swal.close();
    swal.fire({
        'icon': 'success',
        'title': data.title
    }).then(() => {
        window.location.href = data.url;
    });
}