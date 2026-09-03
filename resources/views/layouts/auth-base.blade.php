<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets') }}/images/favicon.ico">

    <!-- Bootstrap Css -->
    <link href="{{ asset('assets') }}/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets') }}/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets') }}/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    {{--
    <link rel="stylesheet" href=""> --}}
    @livewireStyles
</head>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..500&display=swap" rel="stylesheet">

<style>
    * {
        font-family: "Vazirmatn", sans-serif;
    }
</style>

<body>


    <!-- <body data-layout="horizontal"> -->

    <div class="authentication-bg min-vh-100">
        <div class="bg-overlay bg-light"></div>
        <div class="container">
            <div class="d-flex flex-column min-vh-100 px-3 pt-4">
                {{ $slot }}
            </div>
        </div><!-- end container -->
    </div>
    <!-- end authentication section -->

    <!-- JAVASCRIPT -->
    <script>
        window.addEventListener('swal:loginMessage', function(e) {
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
    </script>
    <script src="{{ asset('assets') }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets') }}/libs/metismenujs/metismenujs.min.js"></script>
    <script src="{{ asset('assets') }}/libs/simplebar/simplebar.min.js"></script>
    <script src="{{ asset('assets') }}/libs/eva-icons/eva.min.js"></script>
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>

    <script>
        // simple alert
        window.addEventListener('swal:alert', function(e) {
            swal.fire({
                icon: e.detail.icon,
                title: e.detail.title,
                text: e.detail.text,
                background: e.detail.bg ?? '#fff',
                color: e.detail.cl ?? '#000',
            });
            if (e.detail.url)
                window.location.href = e.detail.url;
        });
    </script>
    @livewireScripts

</body>

</html>
