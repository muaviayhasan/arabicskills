<?php

namespace Tests\Feature;

use App\Livewire\Admin\Auth\Adminino;
use App\Models\Admin;
use App\Models\AdminRole;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The console's front door. It is the one page of the system a stranger can
 * reach, so what it says, what it lets through and what it weighs all matter.
 */
class AdminLoginPageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_page_loads_for_a_signed_out_visitor(): void
    {
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_the_page_names_the_system_in_both_languages(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertSee('Sign in to AST');
        $response->assertSee('Arabic Skill Benchmark Test');
        $response->assertSee('اختبار مهارات اللغة العربية المعياري', false);
        $response->assertSee('بوابة الإدارة', false);
    }

    /**
     * The page carried the whole dashboard theme to draw one form: bootstrap,
     * app.min.css and the icon fonts, around 1.2MB for three glyphs and a
     * button. It stands on its own stylesheet now, and should stay that way.
     */
    public function test_the_page_does_not_pull_the_dashboard_theme(): void
    {
        $html = $this->get(route('admin.login'))->getContent();

        foreach (['app.min.css', 'icons.min.css', 'bootstrap.min.css', 'bootstrap.bundle.min.js', 'sweetalert2'] as $asset) {
            $this->assertStringNotContainsString($asset, $html, $asset.' is back on the sign-in page');
        }

        $this->assertStringContainsString('auth-ast.css', $html);
    }

    /**
     * The checkbox that used to sit here was wired to nothing: it promised to
     * remember a session and never did.
     */
    public function test_the_page_makes_no_promise_it_cannot_keep(): void
    {
        $this->get(route('admin.login'))->assertDontSee('Remember me');
    }

    public function test_both_fields_are_required(): void
    {
        Livewire::test(Adminino::class)
            ->call('login')
            ->assertHasErrors(['email' => 'required', 'password' => 'required']);
    }

    public function test_an_address_that_is_not_an_email_is_rejected(): void
    {
        Livewire::test(Adminino::class)
            ->set('email', 'not-an-address')
            ->set('password', 'whatever')
            ->call('login')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_a_wrong_password_is_refused_on_the_form_itself(): void
    {
        $role = AdminRole::query()->first();
        if ($role === null) {
            $this->markTestSkipped('Need an admin role.');
        }

        $admin = Admin::create([
            'role_id' => $role->id,
            'first_name' => 'Login',
            'last_name' => 'Test',
            'email' => 'login-test-'.random_int(100000, 999999).'@example.test',
            'password' => 'the-right-one',
        ]);

        $component = Livewire::test(Adminino::class)
            ->set('email', $admin->email)
            ->set('password', 'the-wrong-one')
            ->call('login');

        $this->assertFalse(Auth::guard('admin')->check(), 'nobody should be signed in');

        // Said on the page, not in a dialog, and the typed password is dropped.
        $component->assertSee('We could not sign you in');
        $component->assertSet('password', null);
    }
}
