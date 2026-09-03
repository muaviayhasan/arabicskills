<!doctype html>
<html lang="en">

<head>

    <meta charset="utf-8" />
    <title>Error 404</title>
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

</head>


<body>

    <!-- <body data-layout="horizontal"> -->

    <div class="min-vh-100" style="background: url({{ asset('assets') }}/images/bg-2.png) top;">
        <div class="bg-overlay bg-light"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center py-5 mt-5">
                        <div class="position-relative">
                            <h1 class="error-title error-text mb-0">404</h1>
                            <h4 class="error-subtitle text-uppercase mb-0">Opps, You don't have permission</h4>
                            <p class="font-size-16 mx-auto text-muted w-50 mt-4">
                                Perform task within your permissions. Your access violations will be monitored in
                                activity log.
                            </p>
                        </div>
                        <div class="mt-4 text-center">
                            <a class="btn btn-primary" href="{{ URL::previous() }}">Back to page</a>
                        </div>
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end authentication section -->

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets') }}/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets') }}/libs/metismenujs/metismenujs.min.js"></script>
    <script src="{{ asset('assets') }}/libs/simplebar/simplebar.min.js"></script>
    <script src="{{ asset('assets') }}/libs/eva-icons/eva.min.js"></script>

</body>

</html>
