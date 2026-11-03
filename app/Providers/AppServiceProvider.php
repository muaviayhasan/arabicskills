<?php

namespace App\Providers;

use App\Models\Option;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Admin settings are copied into config so that views can ask for
     * config('options.logo') without touching the database.
     *
     * This used to sit in register() and open with Schema::hasTable('options'),
     * and that combination was the single most expensive thing the site did.
     *
     *  - hasTable() is not a cheap existence check. It queries
     *    information_schema.tables, which totals the data and index length of
     *    every table in the database: measured at 51.5ms against production
     *    data, next to 1.0ms for an ordinary indexed lookup. It ran on every
     *    web request, to ask whether a table that has existed since the first
     *    migration still exists, in order to read ten rows.
     *
     *  - It forced a database connection on every request, including the ones
     *    that need no database at all, and held a php process 50ms longer
     *    than necessary. On shared hosting, where concurrency is capped by
     *    process count, that is what turned a login rush into refused
     *    connections: on 2 and 3 November every one of the 1,304 connection
     *    failures logged was this query.
     *
     *  - register() runs before the container is finished, so when it threw,
     *    the app died before the translator existed and Laravel could not
     *    render its own error page. That is where "Target class [translator]
     *    does not exist" came from: not a second fault, the same one.
     *
     * The guard was defensive, for a request arriving before the tables were
     * installed. runningInConsole() below already excludes migrations and
     * seeding, so the only case left was the browser installer route, and that
     * was removed. Nothing needs it now.
     *
     * Settings are cached and cleared on write, so a page costs no query at
     * all, and boot() is late enough that a failure here is an ordinary
     * handled error rather than a dead request.
     */
    public function boot(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $this->loadAdminSettings();
    }

    /**
     * Kept out of boot() so that it can be exercised.
     *
     * A test always counts as running in console and boot() returns early
     * there, so the guard above would make the fallback below impossible to
     * cover: the first version of its test passed just as happily with the
     * catch deleted, which is worse than having no test at all.
     */
    public function loadAdminSettings(): void
    {
        try {
            Option::applyToConfig();
        } catch (\Throwable $e) {
            // Site branding is not worth a white screen. Views fall back to
            // their own defaults, and the reason is recorded once.
            Log::warning('Could not load admin settings into config', ['exception' => $e]);
        }
    }
}
