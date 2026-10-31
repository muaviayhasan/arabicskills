<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ $title ?? 'Sign in' }} · {{ config('options.web_name', 'AST') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Sign in to the Arabic Skill Benchmark Test administration console." name="description" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="shortcut icon" href="{{ asset('assets') }}/images/favicon.ico">

    {{--
        Deliberately not the dashboard theme. Bootstrap, app.min.css and the
        icon fonts together weigh about 770KB and this page used none of it:
        auth-ast.css stands alone and the few icons are inline SVG.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style"
        href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@400;500;600&display=swap">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Vazirmatn:wght@400;500;600&display=swap">

    <link rel="stylesheet" href="{{ versioned_asset('assets/css/auth-ast.css') }}">

    @livewireStyles
</head>

<body>

    <div class="ast-auth">

        <div class="ast-auth__shell">
            {{ $slot }}
        </div>

        <p class="ast-auth__name">
            <span class="ast-auth__name-en">Arabic Skill Benchmark Test</span>
            <span class="ast-auth__name-ar" lang="ar">اختبار مهارات اللغة العربية المعياري</span>
        </p>

    </div>

    @livewireScripts

</body>

</html>
