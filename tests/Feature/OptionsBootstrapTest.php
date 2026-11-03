<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Loading admin settings into config.
 *
 * This ran on every web request as Schema::hasTable('options') followed by a
 * select, inside register(). hasTable queries information_schema.tables,
 * which totals the size of every table in the database: 51.5ms against
 * production data, against 1.0ms for an ordinary lookup, to confirm that a
 * table present since the first migration is still present, so that ten rows
 * could be read.
 *
 * It forced a database connection on every request including those needing
 * none, and because register() runs before the container is complete, a
 * failure there killed the request before the translator existed, so Laravel
 * could not even draw its error page. On 2 and 3 November all 1,304 logged
 * connection failures were this one query.
 */
class OptionsBootstrapTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Option::forgetSettings();
    }

    /** @return list<string> */
    private function queriesDuring(callable $work): array
    {
        $queries = [];

        DB::enableQueryLog();
        DB::flushQueryLog();

        $work();

        foreach (DB::getQueryLog() as $entry) {
            $queries[] = $entry['query'];
        }

        DB::disableQueryLog();

        return $queries;
    }

    /** The whole point: serving a page must not interrogate the schema. */
    public function test_a_request_does_not_query_information_schema(): void
    {
        $queries = $this->queriesDuring(fn () => $this->get('/login')->assertOk());

        foreach ($queries as $query) {
            $this->assertStringNotContainsString(
                'information_schema',
                $query,
                "serving a page ran a schema query:\n".$query
            );
        }
    }

    /**
     * A test always counts as running in console and the provider skips
     * console, which is long-standing behaviour, so the copy is exercised
     * directly rather than through a request.
     */
    public function test_the_settings_still_reach_config(): void
    {
        Option::applyToConfig();

        $this->assertSame(
            Option::query()->where('key', 'web_name')->value('value'),
            config('options.web_name'),
            'views read the site name from config'
        );

        $this->assertNotNull(config('options.logo'), 'the header reads the logo from config');
    }

    /** Once warm, a page costs no query for settings at all. */
    public function test_settings_are_read_from_the_cache_not_the_database(): void
    {
        Option::settings();   // warm

        $queries = $this->queriesDuring(fn () => Option::settings());

        $this->assertSame([], $queries, 'a warm read should not touch the database');
    }

    /** A change on the Settings page has to be live on the next request. */
    public function test_saving_a_setting_clears_the_cache(): void
    {
        Option::settings();
        $this->assertTrue(Cache::has(Option::CACHE_KEY));

        Option::updateOrCreate(['key' => 'web_name'], ['value' => 'Changed In Test']);

        $this->assertFalse(Cache::has(Option::CACHE_KEY), 'writing a setting must drop the memo');
        $this->assertSame('Changed In Test', Option::settings()['web_name']);
    }

    public function test_deleting_a_setting_clears_the_cache(): void
    {
        $option = Option::updateOrCreate(['key' => 'probe_key'], ['value' => 'x']);
        Option::settings();

        $option->delete();

        $this->assertFalse(Cache::has(Option::CACHE_KEY));
    }

    /**
     * Settings are a convenience, not a dependency: if they cannot be read,
     * the request still has to go through, because this runs on every one.
     *
     * Calls the provider directly. The first version of this test made a
     * request instead, which proved nothing: the application has already
     * booted by the time a test body runs, so the mock never reached the code
     * it was meant to break, and the test passed with the catch deleted.
     */
    public function test_a_failure_to_load_settings_is_logged_and_swallowed(): void
    {
        Cache::shouldReceive('remember')->once()->andThrow(new \RuntimeException('database gone'));
        Log::shouldReceive('warning')->once();

        (new AppServiceProvider($this->app))->loadAdminSettings();
    }

    /**
     * Terms are a serialized string, so a missing or damaged one must not be
     * handed to a foreach. Coalescing the result is not enough: unserialize
     * raises a warning on a malformed string and Laravel turns that into an
     * exception.
     */
    public function test_exam_terms_is_always_an_array(): void
    {
        config()->set('options.terms', serialize(['Round One', 'Round Two']));
        $this->assertSame(['Round One', 'Round Two'], exam_terms());

        foreach ([null, '', 'garbage', serialize('a string'), 123] as $broken) {
            config()->set('options.terms', $broken);
            $this->assertSame([], exam_terms(), 'a broken value must still give an array');
        }
    }
}
