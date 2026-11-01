<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Student;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Endpoints that were reachable by anyone on the internet.
 *
 * The log viewer and the cache and migration URLs all sat open on production.
 * Each assertion here is a door that was found unlocked, so none of them is
 * hypothetical: they fail on the code as it stood.
 */
class ExposedEndpointsTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): Admin
    {
        $admin = Admin::query()->first();

        if ($admin === null) {
            $this->markTestSkipped('Need an admin.');
        }

        return $admin;
    }

    /**
     * /install called `migrate:fresh --seed` unless env('APP_ENV') read
     * 'production'. Caching the config stops .env being read at all, so env()
     * returns null, the comparison inverts and the route drops every table.
     * It has no business existing on a deployed site.
     */
    public function test_the_install_route_is_gone(): void
    {
        $this->get('/install')->assertNotFound();
    }

    public function test_the_migrate_route_is_gone(): void
    {
        $this->get('/migrate')->assertNotFound();
    }

    /**
     * Anyone could drop the caches, as often as they liked, and leave the site
     * parsing config and compiling routes on every request afterwards.
     */
    public function test_clearing_the_caches_needs_an_admin(): void
    {
        $this->get('/clear-cache')->assertRedirect();
        $this->assertGuest('admin');
    }

    public function test_an_admin_may_clear_the_caches(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/clear-cache')
            ->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Only production gets its caches rebuilt.
     *
     * This route used to rebuild them everywhere, and this very test suite
     * calls it, so a test run wrote APP_ENV=testing and SESSION_DRIVER=array
     * into bootstrap/cache/config.php and left it there. The running app then
     * read the array session driver, nothing persisted, the CSRF token could
     * never match the session, and every Livewire request answered "This page
     * has expired" -- nobody could sign in until the cache was cleared by
     * hand. Building a config cache outside production is the bug.
     */
    public function test_clearing_the_caches_does_not_build_them_outside_production(): void
    {
        $this->assertFalse(app()->isProduction(), 'the suite must not run as production');

        $this->actingAs($this->admin(), 'admin')->get('/clear-cache');

        $this->assertFileDoesNotExist(
            app()->getCachedConfigPath(),
            'a config cache built here would freeze the testing environment into the app'
        );
        $this->assertFileDoesNotExist(
            app()->getCachedRoutesPath(),
            'a route cache built here would outlive the test run'
        );
    }

    /**
     * The log viewer denies nobody until it is told how to decide: with no
     * auth callback and no viewLogViewer gate its own auth() falls through
     * every branch. The UI and the API both answered anonymous requests, and
     * the API returned real log entries.
     */
    public function test_the_log_viewer_turns_anonymous_visitors_away(): void
    {
        $this->get('/sf-log-viewer')->assertForbidden();
    }

    public function test_the_log_viewer_api_turns_anonymous_visitors_away(): void
    {
        $this->getJson('/sf-log-viewer/api/folders')->assertForbidden();
        $this->getJson('/sf-log-viewer/api/files')->assertForbidden();
        $this->getJson('/sf-log-viewer/api/logs')->assertForbidden();
    }

    public function test_an_admin_may_read_the_logs(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get('/sf-log-viewer')
            ->assertSuccessful();
    }

    /**
     * A student signed in on the web guard is not an admin, and the viewer's
     * own middleware resolves the web guard, so this is the case a gate
     * reading the default guard would have let through.
     */
    public function test_a_signed_in_student_may_not_read_the_logs(): void
    {
        $student = Student::query()->first();

        if ($student === null) {
            $this->markTestSkipped('Need a student.');
        }

        $this->actingAs($student, 'web')
            ->get('/sf-log-viewer')
            ->assertForbidden();
    }

    public function test_the_root_url_still_sends_you_to_the_student_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_coming_soon_still_renders(): void
    {
        $this->get('/coming-soon')->assertSuccessful();
    }
}
