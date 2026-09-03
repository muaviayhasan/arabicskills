<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('options.web_name') }} | {{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('includes') }}/css/style.css?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('includes') }}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('includes') }}/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('assets/select2/select2.min.css') }}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..500&display=swap" rel="stylesheet">


    <style>
        .activity-sticky-clone {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 50;
            border-bottom: 1px solid #ddd;
            background-color: #000053;

            padding: 0.5rem 1rem;

            font-size: 1rem;
            font-weight: 400;
            /* color: #fff !important; */

            margin: 0 !important;

            max-height: 40vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .activity-sticky-clone * {
            color: #fff !important;
        }

        body {
            font-family: "Vazirmatn", sans-serif;
        }

        .no-copy-paste {
            user-select: none;
        }

        .draggable {
            touch-action: none;
            /* 👈 critical */
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }

        .draggable h6 {
            pointer-events: none;
        }

        .word {
            user-select: none;
        }

        @media (hover: none) {
            .word {
                touch-action: none;
            }
        }

        .word.dragging {
            opacity: 0.7;
        }
    </style>
    <style>
        .word {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            margin: 5px;
            background: #f1f1f1;
            border-radius: 6px;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            touch-action: none;
            transition: all 0.2s ease;
            font-size: 14px;
            line-height: 1.4;
        }

        .word:active {
            cursor: grabbing;
        }

        .dropzone .word {
            background: #d1e7dd;
            cursor: default;
        }

        .dropzone {
            min-height: 80px;
            padding: 15px;
            border: 2px dashed #ccc;
            border-radius: 8px;
            transition: background 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .dropzone-placeholder {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #999;
            font-style: italic;
            pointer-events: none;
            white-space: nowrap;
        }

        .word .remove-word {
            border: none;
            background: transparent;
            color: #dc3545;
            font-size: 16px;
            cursor: pointer;
            line-height: 1;
            padding: 0 0 0 4px;
            font-weight: bold;
            transition: transform 0.2s ease;
        }

        .word .remove-word:hover {
            transform: scale(1.2);
        }

        .wordContainer {
            min-height: 50px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .word {
                padding: 10px 14px;
                font-size: 15px;
                margin: 6px;
            }

            .dropzone {
                min-height: 100px;
                padding: 20px 10px;
            }

            .word .remove-word {
                font-size: 18px;
                padding: 0 0 0 6px;
            }
        }
    </style>
    @livewireStyles()
</head>

<body>

    {{ $slot }}

    <!-- ===============script-tag=============== -->
    <script src="{{ asset('includes') }}/js/jquery.js"></script>
    <script src="{{ asset('includes') }}/js/script.js?v={{ time() }}"></script>
    <script src="{{ asset('includes') }}/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('includes/js/take_exam.js') }}?v={{ time() }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.no-copy-paste').forEach(el => {
                el.addEventListener('copy', e => e.preventDefault());
                el.addEventListener('cut', e => e.preventDefault());
                el.addEventListener('paste', e => e.preventDefault());
            });
        })
    </script>
    <script>
        document.addEventListener("scroll", function() {
            const scrollDiv = document.getElementById("scrollDiv");
            const counter = document.getElementById("header-counter");
            if (scrollDiv) {
                if (window.scrollY > 0) {
                    scrollDiv.style.display = "block"; // Show div
                    counter.style.display = "none"; // Hide div
                } else {
                    scrollDiv.style.display = "none"; // Hide div
                    counter.style.display = "block"; // Show div

                }
            }
        });

        const passwordInput = document.getElementById('passwordInput');
        const toggleButton = document.getElementById('toggleButton');

        toggleButton?.addEventListener('click', () => {

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                passwordInput.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });

        // simple alert
        window.addEventListener('swal:alert', function(e) {
            swal.fire({
                icon: e.detail.icon,
                title: e.detail.title,
                text: e.detail.text,
                background: '#fff',
                color: '#000',
            });
            if (e.detail.url)
                window.location.href = e.detail.url;
        });



        document.addEventListener('DOMContentLoaded', () => {
            // Select all audio elements on the page
            const audioElements = document.querySelectorAll('audio');

            audioElements.forEach((audio) => {
                // Add an event listener for the 'play' event
                audio.addEventListener('play', () => {
                    // Pause all other audio elements except the one being played
                    audioElements.forEach((otherAudio) => {
                        if (otherAudio !== audio && !otherAudio.paused) {
                            otherAudio.pause();
                        }
                    });
                });
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const activities = [...document.querySelectorAll(".activity-paragraph")];
            let stickyClone = null;
            let activeIndex = -1;

            window.addEventListener("scroll", () => {

                let newActiveIndex = -1;

                activities.forEach((el, i) => {
                    const rect = el.getBoundingClientRect();
                    if (rect.bottom < 0) {
                        newActiveIndex = i;
                    }
                });

                if (newActiveIndex === -1) {
                    removeSticky();
                    activeIndex = -1;
                    return;
                }

                const next = activities[newActiveIndex + 1];
                if (next) {
                    const nextRect = next.getBoundingClientRect();
                    if (nextRect.top < window.innerHeight && nextRect.bottom > 0) {
                        removeSticky();
                        activeIndex = -1;
                        return;
                    }
                }

                if (activeIndex !== newActiveIndex) {
                    removeSticky();
                    createSticky(activities[newActiveIndex]);
                    activeIndex = newActiveIndex;
                }
            });

            function createSticky(source) {

                stickyClone = source.cloneNode(true);
                stickyClone.classList.remove("activity-paragraph");
                stickyClone.classList.add("activity-sticky-clone", "page-title");
                stickyClone.removeAttribute("tabindex");

                const contentWrapper = document.createElement("div");
                contentWrapper.innerHTML = stickyClone.innerHTML;

                stickyClone.innerHTML = "";
                stickyClone.appendChild(contentWrapper);

                const closeBtn = document.createElement("button");

                closeBtn.classList.add("sticky-close-btn", "btn-close");

                closeBtn.style.cssText =
                    `position: absolute; top: 0.25rem; right: 0.5rem; background-color: red; cursor: Pointer;`;

                stickyClone.appendChild(closeBtn);

                closeBtn.addEventListener("click", () => {
                    removeSticky();
                });

                document.body.appendChild(stickyClone);
            }



            function removeSticky() {
                if (stickyClone) {
                    stickyClone.remove();
                    stickyClone = null;
                }
            }
        });
    </script>
    @livewireScripts()
</body>

</html>
