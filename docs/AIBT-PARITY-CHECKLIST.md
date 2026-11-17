# AIBT parity checklist — issues found and fixed in AST

Everything below is a **defect that was live in AST** and has now been fixed.
AIBT shares AST's lineage, so each one is worth checking there.

For each: what the bug is, how to find out whether AIBT has it, and what the
fix was. Ignore AST's file paths if AIBT's differ — match on the **pattern**,
not the filename.

Order is by how much damage it does, not by when it was found.

---

## A. Security

### A1. `/install` can wipe the database, from the browser, unauthenticated

```php
Route::get('/install', function () {
    if (env('APP_ENV') !== 'production') {
        Artisan::call('migrate:fresh --seed');
    }
});
```

The guard only holds while config is **un**cached. `php artisan config:cache`
stops `.env` being read, so `env()` returns `null`, `null !== 'production'` is
true, and the URL drops every table and reseeds. Caching config for speed arms
a public database wipe.

**Check:** `grep -rn "migrate:fresh\|Artisan::call" routes/`
**Fix:** delete the route. Installation belongs on the command line.

### A2. `/migrate` and `/clear-cache` open to anyone

Unauthenticated routes running migrations and dropping caches.

**Check:** `grep -rn "Artisan::call" routes/`
**Fix:** delete `/migrate`. Put `/clear-cache` behind the admin guard and the
same permission as other destructive admin actions.

### A3. Log viewer serving logs to the public

`opcodesio/log-viewer` denies **nobody** unless you define a `viewLogViewer`
gate or an auth callback. With neither, its own `auth()` falls through every
branch and allows the request. The UI and its API both answer anonymously, and
`api/logs` returns real log entries — stack traces, queries, student details.

**Check:** visit `/sf-log-viewer` (or wherever it is mounted) while logged out.
200 means it is open. Also `grep -rn "viewLogViewer\|LogViewer::auth" app/`.
**Fix:** in `AuthServiceProvider::boot()`:

```php
LogViewer::auth(fn () => auth()->guard('admin')->check());
```

Use the **admin** guard explicitly. A `Gate::define` reading the default guard
locks admins out (they are on `admin`, not `web`) and lets signed-in students
through.

### A4. Uploads accept executable files

Three separate holes:

- activity image validated as `nullable|file` — accepts `.php` as readily as `.png`
- listening audio and match-option uploads had **no file rules at all**
- the student writing upload (handing in photographed work) validated **nothing**

Uploads land in `storage/app/public`, which is symlinked into the web root, and
`store()` keeps the file's original extension. An upload the server will
execute is one the internet can run.

**Check:** `grep -rn "nullable|file\|->store(" app/Http/Controllers/`
Look for any `store()` whose field has no `image`/`mimes` rule.
**Fix:** one shared rule class. Use Laravel's `image` rule, which reads the
file rather than trusting the name, so a script renamed `.png`, `.php.png` or
`.png.php` fails. Cap sizes. For fields that carry **either** text or a file
(a reading passage vs a listening one; a match option that is a word vs a
picture), apply the file rules only when the value is an `UploadedFile`.

### A5. Nothing stops execution in the uploads directory

**Check:** put a harmless `.php` file containing no PHP tags in the uploads
directory and request it. 200 means it is reachable; 403 means blocked.
**Fix:** block script extensions under `/uploads` both at the web root
`.htaccess` and in the upload directory itself. **Wrap every directive in
`<IfModule>`** — a directive the server does not understand turns the whole
directory into a 500.

### A6. A student can submit onto another student's paper

The submit controller takes `exam_id` from a **hidden form field** and passes
it straight to `find()`. Change it in devtools and you write answers onto
someone else's attempt and mark their paper submitted. Verified: that request
returned **200**.

The same lookup also let an already-**checked** (marked) paper be resubmitted
over the top of marked work.

**Check:** `grep -rn "find(\$request->exam_id\|find(\$request->input('exam_id'" app/Http/Controllers/`
**Fix:** resolve the attempt as the signed-in student's own unchecked one:

```php
StudentExam::whereKey((int) $id)
    ->where('student_id', auth()->guard('web')->id())
    ->where('checked', false)
    ->first();
```

---

## B. Loss of student work

### B1. The exam clock is wrong whenever the tab is in the background

The countdown subtracts one second per `setInterval` tick. Browsers throttle
`setInterval` to roughly **once a minute** in a background tab, and the exam
does not pause with it, so a student who switched tabs returns to a clock
showing minutes they no longer have, and the auto-submit fires late.

**Check:** `grep -rn "setInterval" public/**/*.js` — look for a counter being
decremented rather than elapsed time being measured.
**Fix:** derive the remaining time from how much time has actually passed, and
repaint on `visibilitychange`:

```js
const granted = parseInt(timer.dataset.remaining, 10);
const openedAt = Date.now();
const remaining = () => Math.max(0, granted - Math.round((Date.now() - openedAt) / 1000));
```

Use **elapsed time, not an absolute end timestamp**, so a device with a wrong
clock cannot lengthen or shorten the exam.

### B2. A reopened paper opens blank

Reopening gives the student a fresh clock and an empty form, so they must redo
everything they had already answered — the opposite of what reopening is for.

**Check:** reopen a skill for a student who has answers, then open the paper.
**Fix:** load what is already recorded against the attempt and put it back:
chosen option ticked, typed text in the box, rearranged order restored,
matched pairs restored. Restore the visible state by **replaying the same code
path a click goes through**, so the hidden field and what the student sees
cannot drift apart. Leave blank rows out — an auto-submitted paper writes an
empty row per textarea and those are not answers.

### B3. An empty resubmit destroys a previous recording or upload

Speaking audio and uploaded writing pages cannot be put back into a recorder
or a file input. On a retake the field arrives empty, and the controller wrote
`''` over the stored answer — so a student who reopened a speaking paper and
did not re-record **lost the recording they already had**.

**Check:** store an answer, resubmit the same question with an empty value,
see whether the old value survives.
**Fix:** when nothing new arrives for a field the form cannot pre-fill, keep
the previous value. Typed answers **are** pre-filled, so clearing one is a
real deletion and must be honoured.

### B4. One unreadable recording loses the whole paper

Audio that will not base64-decode threw **inside the transaction**, rolling
back every other question too and answering the student with a 500.

**Check:** post a speaking answer with invalid base64.
**Fix:** validate before the transaction opens and return **422 naming the
question to re-record**, so the rest of the paper is never at risk.

### B5. The reopen rule is judged per exam instead of per skill

If "has answers" is a single exam-wide flag, one answer anywhere counts as an
answer everywhere. The ordinary case breaks: a student answers reading, hits
trouble on listening, listening auto-submits blank — and listening, the one
thing that needs handing back, is refused because reading was answered.

**Check:** give an attempt one answered skill and one blank auto-submitted
skill, then see which skills are offered.
**Fix:** ask per skill. An answer reaches a skill through its question's
activity, and the activity carries the type, so that join is the only way to
tell reading work from listening work. Also treat empty answers as *not* work:
an auto-submitted paper writes a blank row per textarea.

### B6. No route to recover a student who crashed mid-paper

Nothing is written until a paper is submitted, so a student who hits a problem
part way through has **no answers recorded**. If the list hides the Details
link when the answer count is zero, and the reopen button skips a window that
is still open, the admin can do nothing at all until the window runs out.

**Check:** find a row with zero answers and see whether any action is offered.
**Fix:** keep the Details link reachable regardless of answer count. That page
carries the per-skill reassign, which hands a skill back whatever state it is in.

### B7. Bulk re-assign: no confirmation, no permission, no school scope

A button that hands papers back to **every student at once**, firing straight
off the click, with `StudentExam::query()` unfiltered — so an admin tied to one
school resets expired skills across every school. It also swept papers already
marked as checked.

**Check:** `grep -rn "reassignAll\|Re-assign All" app/ resources/`
**Fix:** confirm first and say how many rows it will touch; require the same
permission as reopening one row (**check it in the action**, because a
Livewire action on an open component does not re-run the route's middleware);
scope it to the admin's school; exclude checked papers.

---

## C. 500s on pages students and admins actually open

### C1. `redirect()` inside a Livewire component is not a `RedirectResponse`

A method typed `: RedirectResponse` that returns `redirect()` throws a
`TypeError` inside a Livewire component, because Livewire binds its own
`Redirector`. In AST this meant **every student with no level got a 500 on all
five papers**, and the message telling them to contact their school had never
once been shown.

**Check:** `grep -rn ": RedirectResponse" app/Livewire/`
**Fix:** `Redirector|RedirectResponse|null`.

### C2. Route parameter used unvalidated to build a route name

The instructions page took the skill straight from the URL, read a property off
whatever row came back (null for a skill with no row yet), then built a route
name from it. A typo in the address was a 500 twice over.

**Check:** `grep -rn "route(\"student\.\|route('student\." app/Livewire/` for
names built from request input.
**Fix:** constrain the route (`->whereIn('type', [...])`) so anything else is a
404, and default a missing row to an empty string.

### C3. A typed writing answer is unserialized

A writing answer is **either** photographs (a serialized list of upload ids)
**or** text typed on the keyboard tab (plain text). Both admin views
unserialized it either way, and `unserialize()` on plain text raises a warning
that Laravel turns into an exception, taking the page with it. In AST: 128
typed answers across 46 attempts, every one an unopenable marking page.

**Check:** `grep -rn "unserialize(\$question->TakeExam->answer\|unserialize(\$.*->answer" resources/views/`
**Fix:** decide by inspecting the value, not by assuming:

```php
$decoded = preg_match('/^a:\d+:\{/', $raw) === 1 ? @unserialize($raw) : false;
$pages = is_array($decoded) ? $decoded : [];
$typed = is_array($decoded) ? null : $raw;
```

### C4. Reaching through a missing relation

A question a student never touched has **no answer row at all**. Most of the
view read it through `optional()`, but the rearrange branch reached straight
through — so one skipped question 500'd the whole page. In AST this affected
26 of 60 attempts swept.

**Check:** `grep -rn "question->TakeExam->" resources/views/ | grep -v "optional(\|isset(\|?? "`
**Fix:** `optional($question->TakeExam)->answer`, or guard the whole branch
behind `@if (!isset($question->TakeExam->answer) || ...)` as the other
branches already do.

### C5. `json_decode` result handed straight to `foreach`

`json_decode` of anything that is not a JSON array returns a scalar or null,
and looping over that is `foreach() argument must be of type array|object`.

**Check:** `grep -rn "json_decode" resources/views/` and see whether the result
is forced to an array before it is iterated.
**Fix:** `$x = is_array($decoded) ? $decoded : [];`

### C6. Answer `type` column is an enum, and the fallback values are not members

`take_exams.type` is a MySQL enum and the connection runs in
`STRICT_TRANS_TABLES`, so a value outside it **errors** rather than coercing.
The per-skill defaults were `'reading'`, `'listening'`,
`'sentences_structures'` — none of which are members — so an answer arriving
without a type would roll back the entire paper.

**Check:** compare `SHOW COLUMNS FROM take_exams LIKE 'type'` against the
default values used in the submit controller.
**Fix:** fall back to the question's own `type`, which uses the same vocabulary.

### C7. Laravel's own error page cannot render when the container is broken

The bundled 500 view calls the translator. When the failure **is** the
container or the database, the translator is exactly what is missing, so
rendering the error page throws a second exception and the student gets a blank
window. AST logged this 2,706 times in one day.

**Check:** `ls resources/views/errors/` — if there is no `500.blade.php`, the
framework default is in use.
**Fix:** a plain `resources/views/errors/500.blade.php` with **no service
calls at all** — no `__()`, `config()`, `asset()`, `route()`. Assert that in a
test. Tell the student *not to close or reload*, to go back and submit again:
their answers are still in the form, so that is the difference between losing
a paper and losing ten seconds.

---

## D. Capacity — the ones that cause outages under load

> AST refused **1,278 database connections** during one exam with ~1,000
> concurrent students. These are the causes, biggest first.

### D1. `Schema::hasTable()` on every request

```php
public function register(): void {
    if (!app()->runningInConsole()) {
        if (Schema::hasTable('options')) { ... }
    }
}
```

`hasTable()` is **not** a cheap existence check — it queries
`information_schema.tables`, totalling the data and index length of every
table. Measured on production data: **24.7 ms** (up to 55 ms) against 1 ms for
an ordinary lookup — on every request, to confirm a table present since the
first migration still exists, so ten rows of settings could be read.

Three consequences: a database connection is forced on **every** request
including those needing none; every request holds a PHP process ~25 ms longer
(on shared hosting, concurrency is arrival rate × duration — this is how a
login rush becomes refused connections); and because `register()` runs before
the container is built, a failure there kills the request **before the
translator exists**, which is the cause of C7.

**In AST, 100% of the 1,304 connection failures logged across two exam days
were this one query.**

**Check:** `grep -rn "Schema::hasTable\|hasTable(" app/Providers/`
**Fix:** delete the `hasTable()` guard (`runningInConsole()` above it already
excludes migrations); cache the settings and clear the cache on write — hook
the **model's** `saved`/`deleted` events so any write clears it; move the work
from `register()` to `boot()`; wrap it so a failure logs a warning instead of
killing the page.

### D2. Sessions stored in the database

`SESSION_DRIVER=database` costs a read **and** a write over a MySQL socket on
every request. Measured: 3 queries on a login page, all against `sessions`.

**Check:** `grep SESSION_DRIVER .env`
**Fix:** `SESSION_DRIVER=file`. Verify nothing reads the `sessions` table
first, then confirm a real login round trip still returns 200 — that only
passes if the session persisted and the CSRF token matched.

### D3. A blocking geolocation API call during login

`Location::get($ip)` makes an **outbound HTTP request while the student
waits**. Free tiers are rate limited (ip-api.com: 45/min). AST was doing
147–172/min during the login rush, so lookups 429'd and the package fell
through its fallback chain — **up to five sequential external calls per
login**, each holding a PHP process.

The telling detail: AST made **2,135 lookups for 12 distinct IPs** — a school
is one internet connection, and one IP covered 1,085 students.

**Check:** `grep -rn "Location::get" app/`
**Fix:** cache by IP; move it off the login path entirely; or use an offline
database (MaxMind GeoLite2) so there is no network call at all.

### D4. Per-row lookups in list pages

Two patterns found in AST:

- naming a row's level ran **two queries every time** the helper was called,
  once per row — 40 queries for 10 unchanging rows on a 20-row page, and the
  exports did it per exported row
- reading an upload row **per image** on a page — a listening paper paid a
  query per audio clip

**Check:** count queries on a list page with realistic row counts. A thin
local dataset hides this completely — AST's own budget test only caught it
once production data was loaded.
**Fix:** memoise for the request. Hold the memo **on the container**, not in a
static, so it lasts exactly one request and cannot leak between tests.

### D5. One query per answer on submit

`updateOrCreate` inside the answer loop: a 32-question paper cost **67 queries
and ~350 ms**, all of it arriving at once when a room full of students runs out
of time together.

**Check:** `grep -rn "updateOrCreate" app/Http/Controllers/` inside a loop.
**Fix:** read the existing rows in one select, bulk-insert the new ones. Same
paper: **6 queries, 14 ms**, and it no longer grows with question count.

### D6. Assets cache-busted with `time()`

`?v={{ time() }}` changes every second, so the URL is new on every request and
the browser can never reuse anything. If the host already serves these with a
long cache and gzip, the query string throws all of it away.

**Check:** `grep -rn "time()" resources/views/layouts/`
**Fix:** version by the file's own modification time.

### D7. The deploy clears caches and never rebuilds them

If the deploy's last word is `optimize:clear`, production runs with **no config
cache and no route cache** — every request parses every config file and
compiles the whole route table.

**Check:** `ls bootstrap/cache/` on the server. No `config.php` or
`routes-v7.php` means it is running uncached.
**Fix:** `config:cache`, `route:cache`, `view:cache` after the clear.
**Before caching config, check for `env()` calls outside `config/`** (see A1 —
caching config inverts those guards) and for closure routes (`route:cache`
refuses to serialise a closure).

---

## E. Two traps that cost real debugging time

### E1. A route that rebuilds caches, called by the test suite

A `/clear-cache` route that rebuilds after clearing is right on production and
**wrong everywhere else**: building a config cache freezes whichever
environment built it. AST's own test suite called that route, so a test run
wrote `APP_ENV=testing` and `SESSION_DRIVER=array` into
`bootstrap/cache/config.php` and left it there. The running app then used the
array session driver, nothing persisted, the CSRF token could never match —
**every Livewire request answered "This page has expired"** and nobody could
sign in until the cache was cleared by hand.

**Fix:** rebuild on production only. Assert in a test that no config or route
cache survives outside production.

### E2. `UploadedFile::fake()` reports a MIME type guessed from the filename

A fake called `shell.png` claims to be `image/png` whatever is inside it, so an
upload-validation test built on one **passes against unfixed code**. AST's
first version of those tests did exactly that.

**Fix:** build a real file for these tests:

```php
$path = tempnam(sys_get_temp_dir(), 'probe');
file_put_contents($path, "<?php echo 'pwned';");
return new UploadedFile($path, 'shell.png', null, null, true);
```

---

## How to work through this

1. **A1–A3 first.** They are reachable by anyone on the internet right now.
2. **D1** next — it is one small change and, in AST, it was 100% of the logged
   connection failures.
3. **A6, B1, B3, B4** before the next exam. They lose student work.
4. **C1–C7** are each a page that 500s for somebody.
5. The rest as capacity allows.

For anything in B or C, **check production data before assuming it is
theoretical**. Several of these were invisible until a real dataset was loaded
locally — AST swept 60 real attempts across every skill and both admin views
to find C4, which was failing on 26 of them.
