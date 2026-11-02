{{--
    The page a student sees when a request fails.

    Deliberately plain HTML with nothing in it that needs a service: no
    translator, no config, no asset(), no route(). Laravel's own 500 page
    calls the translator, and when the failure is the container or the
    database being unreachable the translator is exactly what is missing, so
    rendering the error page threw a second exception and the student was left
    with a blank window. That happened 1,353 times during one exam.

    The wording matters as much as the page. Nothing a student types is saved
    until they submit, so a failed submit leaves their answers sitting in the
    browser form and nowhere else. Telling them to go back and try again,
    rather than reload or close, is the difference between losing a paper and
    losing ten seconds.
--}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Please try again</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #05003f;
            color: #f8f3e4;
            font-family: system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
            padding: 24px;
        }

        .box {
            max-width: 560px;
            text-align: center;
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 500;
            margin: 0 0 12px;
        }

        p {
            font-size: 1.05rem;
            line-height: 1.6;
            margin: 0 0 14px;
            color: #ded8c6;
        }

        .do {
            background: #0d0a52;
            border: 1px solid #b2913c;
            border-radius: 12px;
            padding: 18px 20px;
            margin: 20px 0 0;
            text-align: left;
        }

        .do strong {
            color: #f8f3e4;
        }

        ol {
            margin: 10px 0 0;
            padding-inline-start: 22px;
        }

        li {
            margin: 6px 0;
            color: #ded8c6;
        }

        .ar {
            margin-top: 18px;
            font-size: 1.05rem;
            color: #ded8c6;
        }
    </style>
</head>

<body>
    <div class="box">
        <h1>The system is busy</h1>

        <p>Your request did not go through. This is the system, not you.</p>

        <div class="do">
            <strong>If you were taking an exam:</strong>
            <ol>
                <li>Do not close this window and do not reload.</li>
                <li>Press your browser's back button. Your answers are still on the exam page.</li>
                <li>Submit again.</li>
            </ol>
        </div>

        <p class="ar" lang="ar" dir="rtl">النظام مشغول. لا تغلق الصفحة ولا تحدّثها. ارجع إلى الخلف وأرسل إجاباتك مرة
            أخرى.</p>
    </div>
</body>

</html>
