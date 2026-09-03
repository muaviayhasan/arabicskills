<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>{{ config('options.web_name') }} | {{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <!-- csrf token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- Bootstrap Css -->
    <link href="{{ asset('assets') }}/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets') }}/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets') }}/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets') }}/css/admin-custom.css" rel="stylesheet" type="text/css" />

    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/select2/select2.min.css') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..500&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: "Vazirmatn", sans-serif;
        }
    </style>
    @livewireStyles
</head>


<body>

    <!-- <body data-layout="horizontal"> -->

    <!-- Begin page -->
    <div id="layout-wrapper">


        <header id="page-topbar" class="isvertical-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <!-- LOGO -->
                    <div class="navbar-brand-box">
                        <a href="{{ route('admin.dashboard') }}" class="logo logo-dark">
                            <span class="logo-sm">
                                <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                                    height="26">
                            </span>
                            <span class="logo-lg">
                                <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                                    height="26">
                            </span>
                        </a>

                        <a href="{{ route('admin.dashboard') }}" class="logo logo-light">
                            <span class="logo-lg">
                                <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                                    height="30">
                            </span>
                            <span class="logo-sm">
                                <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                                    height="26">
                            </span>
                        </a>
                    </div>

                    <button type="button"
                        class="btn btn-sm font-size-24 header-item waves-effect vertical-menu-btn px-3">
                        <i class="bx bx-menu align-middle"></i>
                    </button>

                </div>

                <div class="d-none d-md-block mt-3">
                    <h3>{{ config('options.web_name', 'Default') }}</h3>
                </div>

                {{-- User dropdown --}}

                <div class="d-inline-block">
                    <button type="button" class="btn header-item user d-flex align-items-center text-start"
                        id="page-header-user-dropdown-v" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <img class="rounded-circle header-profile-user changeprofile"
                            src="{{ image('uploads/admins', Auth::guard('admin')->user()->image) }}"
                            alt="Header Avatar">
                        <span
                            class="d-none d-xl-inline-block fw-medium font-size-15 ms-2">{{ ucwords(Auth::guard('admin')->user()->first_name . ' ' . Auth::guard('admin')->user()->last_name) }}</span>
                    </button>

                </div>
            </div>
        </header>
        <!-- ========== Left Sidebar Start ========== -->
        <div class="vertical-menu">

            <!-- LOGO -->
            <div class="navbar-brand-box">
                <a href="{{ route('admin.add-admin') }}" class="logo logo-dark">
                    <span class="logo-sm" style="width: 50px !important;">
                        <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                            height="26" width="100%">
                    </span>
                    <span class="logo-lg">
                        <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                            height="50px" width="50%">
                    </span>
                </a>

                <a href="{{ route('admin.add-admin') }}" class="logo logo-light">
                    <span class="logo-lg">
                        <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                            height="50px" width="50%">
                    </span>
                    <span class="logo-sm" style="width: 50px !important;">
                        <img src="{{ image('uploads/logo', config('options.logo'), 'dummy-logo.png') }}" alt=""
                            height="26" width="100%">
                    </span>
                </a>
            </div>

            <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect vertical-menu-btn">
                <i class="bx bx-menu align-middle"></i>
            </button>

            <div data-simplebar class="sidebar-menu-scroll">

                <!--- Sidemenu -->
                <div id="sidebar-menu">
                    <!-- dashboard li -->
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li class="menu-title" data-key="t-menu">Dashboard</li>

                        <li class="mb-1 {{ request()->routeIs(['admin.dashboard']) ? 'mm-active' : '' }}">
                            <a class="active" href="{{ route('admin.dashboard') }}">
                                <i class="bx bx-home-alt icon nav-icon"></i>
                                <span class="menu-item">Dashboard</span>
                            </a>
                        </li>

                        @if (getPermissions('schools', 'view'))
                            <!--Admin li -->
                            <li class="menu-title" data-key="t-menu">Schools Management</li>

                            <li
                                class="mb-1 {{ request()->routeIs(['admin.schools', 'admin.edit-school', 'admin.add-school', 'admin.grades-and-sections']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.schools') }}">
                                    <i class='bx bxs-school icon nav-icon'></i>
                                    <span class="menu-item">Schools</span>
                                </a>

                            </li>
                        @endif

                        @if (getPermissions('classes', 'view'))
                            <li class="mb-1 {{ request()->routeIs(['admin.grades']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.grades') }}">
                                    <i class='bx bx-layer icon nav-icon'></i>
                                    <span class="menu-item">Grades/Years</span>
                                </a>
                            </li>
                        @endif

                        @if (getPermissions('students', 'view'))
                            <li class="menu-title" data-key="t-menu">Students Management</li>

                            <li
                                class="mb-1 {{ request()->routeIs(['admin.students', 'admin.edit-student', 'admin.add-student']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.students') }}">
                                    <i class='bx bx-face icon nav-icon'></i>
                                    <span class="menu-item">Students</span>
                                </a>

                            </li>

                            <li class="mb-1 {{ request()->routeIs('admin.students-without-exams') ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.students-without-exams') }}">
                                    <i class='bx bx-user-x icon nav-icon'></i>
                                    <span class="menu-item">Students Without Exams</span>
                                </a>
                            </li>

                            <li class="mb-1 {{ request()->routeIs(['admin.device-tests', 'admin.device-tests.details']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.device-tests') }}">
                                    <i class='bx bx-laptop icon nav-icon'></i>
                                    <span class="menu-item">Device Tests</span>
                                </a>
                            </li>
                        @endif

                        @if (getPermissions('question_banks', 'view') || getPermissions('exams', 'view'))
                            <li class="menu-title" data-key="t-menu">Exams Management</li>
                        @endif

                        @if (getPermissions('question_banks', 'view'))
                            <li
                                class="mb-1 {{ request()->routeIs(['admin.question-banks', 'admin.edit-question-bank']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.question-banks') }}">
                                    <i class='bx bx-question-mark icon nav-icon'></i>
                                    <span class="menu-item">Question Banks</span>
                                </a>

                            </li>

                            <li>
                                <a class="mb-1 {{ request()->routeIs('admin.add-question-bank') ? 'mm-active' : '' }}"
                                    href="{{ route('admin.add-question-bank', ['type' => 'reading']) }}" class="has-arrow">
                                    <i class="bx bx-plus-circle icon nav-icon"></i>
                                    <span class="menu-item">Add Assessment</span>
                                </a>
                                <ul class="sub-menu" aria-expanded="true">
                                    <li><a href="{{ route('admin.add-question-bank', ['type' => 'reading']) }}"
                                            class="{{ request()->routeIs('admin.add-question-bank', ['type' => 'reading']) ? 'active' : '' }}">Reading</a>
                                    </li>
                                    <li><a href="{{ route('admin.add-question-bank', ['type' => 'listening']) }}"
                                            class="{{ request()->routeIs('admin.add-question-bank', ['type' => 'listening']) ? 'mm-active' : '' }}">Listening</a>
                                    </li>
                                    <li><a href="{{ route('admin.add-question-bank', ['type' => 'writing']) }}"
                                            class="{{ request()->routeIs('admin.add-question-bank', ['type' => 'writing']) ? 'mm-active' : '' }}">Writing</a>
                                    </li>
                                    <li><a href="{{ route('admin.add-question-bank', ['type' => 'speaking']) }}"
                                            class="{{ request()->routeIs('admin.add-question-bank', ['type' => 'speaking']) ? 'mm-active' : '' }}">Speaking</a>
                                    </li>

                                    <li><a href="{{ route('admin.add-question-bank', ['type' => 'sentences_structures']) }}"
                                            class="{{ request()->routeIs('admin.add-question-bank', ['type' => 'sentences_structures']) ? 'mm-active' : '' }}">Sentences
                                            Structures</a>
                                    </li>

                                </ul>
                            </li>
                        @endif

                        @if (getPermissions('exams', 'view'))
                            <li
                                class="mb-1 {{ request()->routeIs(['admin.exams', 'admin.add-exam', 'admin.edit-exam', 'admin.exam-reading-activities', 'admin.exam-listening-activities', 'admin.exam-writing-activities', 'admin.exam-speaking-activities']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.exams') }}">
                                    <i class='bx bx-clipboard icon nav-icon'></i>
                                    <span class="menu-item">Exams</span>
                                </a>

                            </li>
                        @endif

                        <!--results li -->
                        @if (getPermissions('results', 'view'))
                            <li class="menu-title" data-key="t-menu">Results Management</li>
                            <li
                                class="mb-1 {{ request()->routeIs(['admin.attempted-exams', 'admin.check-exam']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.attempted-exams') }}">
                                    <i class='bx bx-check-square icon nav-icon'></i>
                                    <span class="menu-item">Exams Check</span>
                                </a>

                            </li>

                            <li class="mb-1 {{ request()->routeIs(['admin.results']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.results') }}">
                                    <i class='bx bx-filter-alt icon nav-icon'></i>
                                    <span class="menu-item">Results</span>
                                </a>

                            </li>
                        @endif


                        @if (getPermissions('profile', 'view'))
                            <!--profile li -->
                            <li class="menu-title" data-key="t-menu">Profile</li>

                            <li class="mb-1 {{ request()->routeIs('admin.profile') ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.profile') }}">
                                    <i class='bx bx-user-circle icon nav-icon'></i>
                                    <span class="menu-item">Profile</span>
                                </a>

                            </li>
                        @endif

                        @if (getPermissions('admins', 'view') || getPermissions('admin_roles', 'view'))
                            <li class="menu-title" data-key="t-menu">Admins Management</li>
                        @endif
                        @if (getPermissions('admins', 'view'))
                            <!--Admin li -->


                            <li
                                class="mb-1 {{ request()->routeIs(['admin.admins', 'admin.edit-admin', 'admin.add-admin']) ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.admins') }}">
                                    <i class='bx bx-user icon nav-icon'></i>
                                    <span class="menu-item">Admins</span>
                                </a>

                            </li>
                        @endif

                        @if (getPermissions('admin_roles', 'view'))
                            <!--role permission li -->

                            <li class="mb-1 {{ request()->routeIs('admin.role-permission') ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.role-permission') }}">
                                    <i class='bx bx-lock-alt icon nav-icon'></i>
                                    <span class="menu-item">Role Permissions</span>
                                </a>

                            </li>
                        @endif
                        @if (getPermissions('options', 'view'))
                            <li class="menu-title" data-key="t-menu">Site Options</li>

                            <li class="mb-1 {{ request()->routeIs(['admin.settings']) ? 'mm-active' : '' }}">
                                <a class="active" href="{{ route('admin.settings') }}">
                                    <i class="bx bx-cog icon nav-icon"></i>
                                    <span class="menu-item">Settings</span>
                                </a>
                            </li>

                            @if (getPermissions('levels', 'view'))
                                <li class="mb-1 {{ request()->routeIs(['admin.levels']) ? 'mm-active' : '' }}">
                                    <a class="active" href="{{ route('admin.levels') }}">
                                        <i class="bx bx-layer icon nav-icon"></i>
                                        <span class="menu-item">Levels</span>
                                    </a>
                                </li>
                            @endif

                            <li class="mb-1 {{ request()->routeIs(['admin.instruction-pages']) ? 'mm-active' : '' }}">
                                <a class="active" href="{{ route('admin.instruction-pages') }}">
                                    <i class="bx bx-file-blank icon nav-icon"></i>
                                    <span class="menu-item">Instruction Pages</span>
                                </a>
                            </li>

                            <!--logs li -->
                            <li class="menu-title" data-key="t-menu">Login Info</li>

                            <li class="mb-1 {{ request()->routeIs('admin.auth-logs') ? 'mm-active' : '' }}">
                                <a href="{{ route('admin.auth-logs') }}">
                                    <i class='bx bx-list-ul icon nav-icon'></i>
                                    <span class="menu-item">Auth Logs</span>
                                </a>

                            </li>
                        @endif

                        <!--logout li -->
                        <li class="menu-title" data-key="t-menu">Logout</li>

                        <li class="mb-1">
                            <a href="{{ route('admin.logout') }}">
                                <i class='bx bx-log-out-circle icon nav-icon'></i>
                                <span class="menu-item">logout</span>
                            </a>

                        </li>

                    </ul>
                </div>
                <!-- Sidebar -->
            </div>
        </div>
        <!-- Left Sidebar End -->
        <header class="ishorizontal-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <!-- LOGO -->
                    <button type="button" class="btn btn-sm px-3 font-size-24 d-lg-none header-item"
                        data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                        <i class="bx bx-menu align-middle"></i>
                    </button>
                </div>
            </div>

            <div class="topnav">
                <div class="container-fluid">
                    <nav class="navbar navbar-light navbar-expand-lg topnav-menu">

                    </nav>
                </div>
            </div>
        </header>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <x-breadcrumb :page_title="$pageTitle" :breadcrumb="$breadcrumb" />


                    {{ $slot }}
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-12">
                            {{ date('Y') }} &copy; {{ config('options.web_name') }}
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->


    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets') }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets') }}/libs/metismenujs/metismenujs.min.js"></script>
    <script src="{{ asset('assets') }}/libs/simplebar/simplebar.min.js"></script>
    <script src="{{ asset('assets') }}/libs/eva-icons/eva.min.js"></script>
    <script src="{{ asset('assets') }}/js/app.js"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <Script src="{{ asset('assets/js/jquery-ui.min.js') }}"></Script>

    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('assets/js/alerts.js') }}"></script>
    <script src="{{ asset('assets/js/question_banks.js') }}"></script>

    <!-- include summernote css/js -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>

    <script src="{{ asset('assets/select2/select2.mis.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.js-example-basic-multiple').select2({
                width: 'resolve',
                tokenSeprators: [',', ' ']
            });

            $('.select2Option').select2({
                width: 'resolve',
                tags: true,
                tokenSeparators: [',', ' ']
            });

            $('.terms-select2').select2({
                width: 'resolve',
                tags: true,
                tokenSeprators: [',', ' ']
            });

            function initExamLevelIdsSelect2() {
                $('.exam-level-ids-select2').each(function () {
                    var $el = $(this);

                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }

                    $el.select2({
                        width: '100%',
                        placeholder: 'Select exam levels',
                        allowClear: true
                    });
                });
            }

            initExamLevelIdsSelect2();


            $(document).on('change', '.select2Option', function (e) {
                var valu = $(this).val();
                var key = $(this).attr('id');

                Livewire.dispatch('updateTabs', {
                    key: key,
                    valu: valu
                });
            });

            $(document).on('change', '.terms-select2', function (e) {
                var valu = $(this).val();
                Livewire.dispatch('updateTabs', {
                    'key': 'terms',
                    valu: valu
                });
            });

            $(document).on('change', '.exam-level-ids-select2', function () {
                var vals = $(this).val() || [];

                Livewire.dispatch('updateExamLevelIds', {
                    levelIds: vals.map(function (value) {
                        return parseInt(value, 10);
                    }).filter(function (value) {
                        return !isNaN(value);
                    })
                });
            });

            function applyExamLevelIdsSync(levelIds) {
                $('.exam-level-ids-select2').each(function () {
                    var $el = $(this);

                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }

                    $el.val((levelIds || []).map(String));
                    $el.select2({
                        width: '100%',
                        placeholder: 'Select exam levels',
                        allowClear: true
                    });
                });
            }

            document.addEventListener('livewire:init', function () {
                Livewire.on('exam-level-ids-sync', function (payload) {
                    var data = payload;

                    if (Array.isArray(payload) && payload.length) {
                        data = payload[0];
                    }

                    if (!data) {
                        return;
                    }

                    applyExamLevelIdsSync(data.levelIds || []);
                });
            });

            $(document).on("change", "#searchColumn", function (e) {
                e.preventDefault();
                $("#searchColumnError").text('');

                var type = $(this).val();

                Livewire.dispatch('manageSearch', {
                    searchWord: '',
                    searchColumn: type
                });

                var placeholder = type.replace('_', ' ');

                if (type == "featured") {
                    $("#searchInputContainer").html(`
                                <select id="searchSelect" name="featured" class="form-control bg-light border-light float-end rounded">
                                <option value="" selected>Select Filter</option>
                                <option value="1">Featured</option>
                                <option value="0">Restrained</option>
                            </select>
                            `);
                } else if (type == "status") {
                    $("#searchInputContainer").html(`
                                <select id="searchSelect" name="status" class="form-control bg-light border-light float-end rounded">
                                <option value="" selected>Select Filter</option>
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                            </select>
                            `);
                } else if (type == "active") {
                    $("#searchInputContainer").html(`
                                <select id="searchSelect" name="active" class="form-control bg-light border-light float-end rounded">
                                <option value="" selected>Select Filter</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            `);
                } else if (type == "available") {
                    $("#searchInputContainer").html(`
                                <select id="searchSelect" name="available" class="form-control bg-light border-light float-end rounded">
                                <option value="" selected>Select Filter</option>
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                            `);
                } else if (type == "type") {
                    $("#searchInputContainer").html(`
                                <select id="searchSelect" name="type" class="form-control bg-light border-light float-end rounded">
                                <option value="" selected>Select Filter</option>
                                <option value="reading">Reading</option>
                                <option value="writing">Writing</option>
                                <option value="listening">Listening</option>
                                <option value="speaking">Speaking</option>
                            </select>
                            `);
                } else if (type == "deleted_at") {
                    $("#searchInputContainer").html(`
                                <select id="searchSelect" name="deleted_at" class="form-control bg-light border-light float-end rounded">
                                <option value="deleted_at" selected>Deleted</option>
                            </select>
                            `);
                } else {
                    $("#searchInputContainer").html(`
                                <input type="text" id="searchWord"
                                class="form-control bg-light border-light float-end rounded"
                                placeholder="Type ${placeholder}">
                            `);
                }
            });

            $(document).on("change", "#searchSelect", function (e) {
                e.preventDefault();
                var value = $(this).val();
                var type = $(this).attr('name');
                Livewire.dispatch('manageSearch', {
                    searchWord: value,
                    searchColumn: type
                });
            });

            $(document).on("keyup", "#searchWord", function (e) {
                e.preventDefault();
                var type = $("#searchColumn").val();
                if (type == '') {
                    $("#searchColumnError").text('Please first select search type');
                } else {
                    $("#searchColumnError").text('');

                    var value = $(this).val();
                    Livewire.dispatch('manageSearch', {
                        searchWord: value,
                        searchColumn: type
                    });
                }
            });
        });

        $(document).on('change', '.js-example-basic-multiple', function (e) {
            var valu = $(this).val();
            Livewire.dispatch('updateTabs', {
                valu: valu
            });
        });



        $(document).ready(function () {
            function updateLivewireInputs(editorId) {
                var inputId = editorId.replace('_summernote', '');
                var contents = $('#' + editorId).summernote('code');
                Livewire.dispatch('updateInput', {
                    inputId: inputId,
                    contents: contents
                });
            }

            $('.summernote').each(function () {
                var $summernote = $(this);
                var editorId = $summernote.attr('id');

                $summernote.summernote({
                    toolbar: [

                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['view', ['fullscreen', 'codeview', 'help']],
                    ]
                }).on('summernote.change', function () {
                    updateLivewireInputs(editorId);
                });
            });

            $('div.note-editable').css({
                'min-height': 250 + 'px'
            });
        });


        setTimeout(() => {
            $('.note-btn-group ul.note-dropdown-menu').addClass('mt-4');

            // Add a click event listener to the dropdown toggle button
            $(document).on('click', '.note-btn-group .dropdown-toggle', function (e) {
                e.stopPropagation(); // Prevent event from bubbling to avoid unintended behavior

                // Find the <ul> element within the clicked group
                const dropdownMenu = $(this).parent().find('ul.note-dropdown-menu');

                // Close all other dropdown menus by removing 'd-block' from their <ul>
                $('.note-btn-group ul.note-dropdown-menu').not(dropdownMenu).removeClass('d-block');

                if (dropdownMenu.length) {
                    // Toggle the current dropdown menu
                    dropdownMenu.toggleClass('d-block');
                }
            });

            // Optional: Close dropdown when clicking outside
            $(document).on('click', function () {
                $('.note-btn-group ul.note-dropdown-menu').removeClass('d-block');
            });
        }, 1500);

        document.querySelectorAll('input[type=number]').forEach(function (input) {
            input.addEventListener('wheel', function (event) {
                event.preventDefault();
            });
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

    @livewireScripts
</body>

</html>