<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * What a student sees when a request fails.
 *
 * Laravel's bundled 500 page calls the translator. When the failure is the
 * container or the database being unreachable, the translator is exactly what
 * is missing, so rendering the error page threw a second exception and the
 * student got a blank window: that happened 1,353 times during one exam,
 * alongside 1,278 refused database connections.
 */
class ErrorPageTest extends TestCase
{
    public function test_the_error_page_needs_nothing_from_the_container(): void
    {
        $compiled = app('blade.compiler')->compileString(
            file_get_contents(resource_path('views/errors/500.blade.php'))
        );

        foreach (['__(', 'trans(', 'config(', 'route(', 'asset(', 'app('] as $call) {
            $this->assertStringNotContainsString(
                $call,
                $compiled,
                "the error page must not call {$call}: it has to render when services are gone"
            );
        }
    }

    public function test_a_failed_request_shows_the_student_what_to_do(): void
    {
        config(['app.debug' => false]);

        Route::get('/__error_probe', function () {
            throw new \RuntimeException('simulated failure');
        })->middleware('web');

        $response = $this->get('/__error_probe');

        $response->assertStatus(500);

        // The instruction that saves a paper: their answers are still in the
        // form, so going back beats reloading or closing.
        $response->assertSee('back button', false);
        $response->assertSee('Do not close this window', false);
        $response->assertSee('النظام مشغول', false);
    }

    /**
     * And not Laravel's own, which is the one that could not render.
     */
    public function test_the_framework_default_page_is_not_used(): void
    {
        config(['app.debug' => false]);

        Route::get('/__error_probe_two', function () {
            throw new \RuntimeException('simulated failure');
        })->middleware('web');

        $this->get('/__error_probe_two')
            ->assertStatus(500)
            ->assertDontSee('Server Error', false);
    }
}
