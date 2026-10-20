<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Budi Santoso',
            'username' => 'Budi_S',
            'email' => 'BUDI@example.com',
            'password' => 'Secret#123',
            'password_confirmation' => 'Secret#123',
            'terms' => '1',
        ])->assertRedirect('/home');

        $user = User::where('email', 'budi@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('budi_s', $user->username);
        $this->assertFalse($user->is_admin);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_rejects_weak_passwords_and_mass_assigned_admin(): void
    {
        $this->post('/register', [
            'name' => 'Weak', 'username' => 'weak', 'email' => 'weak@example.com',
            'password' => 'password', 'password_confirmation' => 'password', 'terms' => '1',
        ])->assertSessionHasErrors('password');

        $this->post('/register', [
            'name' => 'Sneaky', 'username' => 'sneaky', 'email' => 'sneaky@example.com',
            'password' => 'Secret#123', 'password_confirmation' => 'Secret#123', 'terms' => '1', 'is_admin' => '1',
        ]);

        $this->assertFalse(User::where('email', 'sneaky@example.com')->first()->is_admin);
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'Secret#123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_fails_with_generic_message_and_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'Secret#123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors(['email' => __('auth.failed')]);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'Secret#123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_can_be_verified_with_signed_link(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/profile');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $user = User::factory()->create(['password' => 'Secret#123', 'username' => 'old']);

        $this->actingAs($user)->put('/profile', [
            'name' => 'New Name', 'username' => 'new_name', 'date_of_birth' => '1995-05-05', 'phone' => '+62 811 1111 2222',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('1995-05-05', $user->date_of_birth->toDateString());

        $this->put('/profile/password', [
            'current_password' => 'wrong', 'password' => 'Another#456', 'password_confirmation' => 'Another#456',
        ])->assertSessionHasErrorsIn('password', 'current_password');

        $this->put('/profile/password', [
            'current_password' => 'Secret#123', 'password' => 'Another#456', 'password_confirmation' => 'Another#456',
        ])->assertSessionHasNoErrors();
    }
}
