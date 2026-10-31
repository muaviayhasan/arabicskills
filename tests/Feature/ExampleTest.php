<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The stock version of this asserted 200 and so had always failed: the
     * site has no landing page and sends the root to the student login.
     */
    public function test_the_application_responds_at_the_root(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
