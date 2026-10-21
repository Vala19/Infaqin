<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_log_in_and_gets_the_generic_message(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'Secret#123']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest();
    }

    public function test_inactive_login_message_matches_a_wrong_password(): void
    {
        $inactive = User::factory()->inactive()->create(['password' => 'Secret#123']);
        $active = User::factory()->create(['password' => 'Secret#123']);

        $inactiveErrors = $this->post('/login', ['email' => $inactive->email, 'password' => 'Secret#123'])
            ->baseResponse->getSession()->get('errors')->get('email');
        $wrongErrors = $this->post('/login', ['email' => $active->email, 'password' => 'wrong'])
            ->baseResponse->getSession()->get('errors')->get('email');

        $this->assertSame($wrongErrors, $inactiveErrors);
    }

    public function test_inactive_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'Secret#123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])
                ->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])
            ->assertSessionHasErrors('email')
            ->assertSessionDoesntHaveErrors(['email' => __('auth.failed')]);
    }

    public function test_reactivated_user_can_log_in_again(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'Secret#123']);
        $user->forceFill(['is_active' => true])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_session_of_deactivated_user_ends_on_next_request(): void
    {
        $user = User::factory()->create(['password' => 'Secret#123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])->assertRedirect('/home');
        $this->get('/home')->assertOk();

        $user->forceFill(['is_active' => false])->save();
        // The test app keeps the guard's cached user between requests; a real request starts fresh.
        $this->app['auth']->forgetGuards();

        $this->get('/home')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => __('Your session has ended. Please log in again.')]);
        $this->assertGuest();

        $this->get('/home')->assertRedirect('/login');
    }

    public function test_deactivated_user_is_logged_out_on_public_pages_too(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->forceFill(['is_active' => false])->save();

        $this->get('/campaigns')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_active_user_session_is_untouched(): void
    {
        $this->actingAs(User::factory()->create())->get('/home')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_is_active_defaults_to_true_for_new_users(): void
    {
        $this->assertTrue((new User)->is_active);
    }
}
