<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function credentials(User $user, string $token, array $overrides = []): array
    {
        return array_replace(['email' => $user->email, 'token' => $token,
            'password' => 'New-password1!', 'password_confirmation' => 'New-password1!'], $overrides);
    }

    public function test_login_and_reset_forms_are_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'), false);
        $this->get(route('password.request'))->assertOk()->assertSee('ocean-auth', false);
        $this->get(route('password.reset', ['token' => 'example', 'email' => 'customer@example.test']))
            ->assertOk()->assertSee('customer@example.test')->assertSee('data-password-monitor', false)
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_admin_and_customer_receive_single_use_links_without_exposing_unknown_emails(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'member_id' => Member::factory()->create()->id]);
        foreach ([$admin, $customer] as $user) {
            $this->from(route('password.request'))->post(route('password.email'), ['email' => ' '.strtoupper($user->email).' '])
                ->assertRedirect(route('password.request'))->assertSessionHasNoErrors()->assertSessionHas('status');
            Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
                $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
                $this->assertNotSame($notification->token, $stored);
                $this->assertTrue(Hash::check($notification->token, $stored));
                $mail = $notification->toMail($user);
                $this->assertStringStartsWith(rtrim(config('app.url'), '/').'/reset-password/', $mail->actionUrl);
                $this->assertSame('Atur ulang password akun Ocean Paws', $mail->subject);
                return true;
            });
        }
        $message = session('status');
        $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertSessionHas('status', $message);
        $this->post(route('password.email'), ['email' => $admin->email])->assertSessionHas('status', $message);
        Notification::assertSentToTimes($admin, ResetPassword::class, 1);
        Notification::assertCount(2);
    }

    public function test_both_roles_reset_password_and_login_with_the_correct_redirect(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Event::fake([PasswordReset::class]);
        foreach ([User::ROLE_ADMIN, User::ROLE_CUSTOMER] as $role) {
            $user = User::factory()->create(['role' => $role,
                'member_id' => $role === User::ROLE_CUSTOMER ? Member::factory()->create()->id : null]);
            $identity = $user->sessionIdentity();
            $remember = $user->remember_token;
            $token = Password::createToken($user);
            $this->post(route('password.update'), $this->credentials($user, $token))
                ->assertSessionHasNoErrors()->assertRedirect(route('login'));
            $this->assertGuest();
            $fresh = $user->fresh();
            $this->assertTrue(Hash::check('New-password1!', $fresh->password));
            $this->assertSame($role, $fresh->role);
            $this->assertNotNull($fresh->password_set_at);
            $this->assertNotSame($identity, $fresh->sessionIdentity());
            $this->assertNotSame($remember, $fresh->remember_token);
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
            $this->post(route('password.update'), $this->credentials($user, $token))->assertSessionHasErrors('email');
            $this->post(route('login'), ['username' => $user->email, 'password' => 'New-password1!'])
                ->assertRedirect(route($role === User::ROLE_ADMIN ? 'admin.dashboard' : 'profile.show'));
            $this->post(route('logout'));
        }
        Event::assertDispatchedTimes(PasswordReset::class, 2);
    }

    public function test_invalid_expired_or_wrong_account_tokens_cannot_change_password(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $original = $user->password;
        $token = Password::createToken($user);
        $this->post(route('password.update'), $this->credentials($user, 'invalid'))->assertSessionHasErrors('email');
        $this->post(route('password.update'), $this->credentials($other, $token))->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post(route('password.update'), $this->credentials($user, $token))->assertSessionHasErrors('email');
        $this->assertSame($original, $user->fresh()->password);
    }

    public function test_weak_password_mismatch_and_role_changes_are_rejected(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $user = User::factory()->create();
        $token = Password::createToken($user);
        foreach (['short', 'lowercase1!', 'UPPERCASE1!', 'NoNumbers!', 'NoSymbols1'] as $weak) {
            $this->post(route('password.update'), $this->credentials($user, $token, [
                'password' => $weak, 'password_confirmation' => $weak,
            ]))->assertSessionHasErrors('password');
        }
        $this->post(route('password.update'), $this->credentials($user, $token, ['password_confirmation' => 'Different-password1!']))
            ->assertSessionHasErrors('password');
        $this->post(route('password.update'), $this->credentials($user, $token, ['role' => 'customer']))->assertSessionHasErrors('role');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_pending_customer_reset_does_not_bypass_approval_or_activate_member(): void
    {
        $member = Member::factory()->create(['is_active' => false]);
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'member_id' => $member->id,
            'registration_pending' => true, 'password_set_at' => null]);
        $this->post(route('password.update'), $this->credentials($user, Password::createToken($user)))
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->registration_pending);
        $this->assertFalse($member->fresh()->is_active);
        $this->post(route('login'), ['username' => $user->email, 'password' => 'New-password1!'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_reset_request_is_rate_limited_and_email_is_validated(): void
    {
        Notification::fake();
        $this->post(route('password.email'), ['email' => 'invalid'])->assertSessionHasErrors('email');
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertRedirect();
        }
        $this->post(route('password.email'), ['email' => 'missing@example.test'])->assertStatus(429);
    }

    public function test_email_delivery_failure_returns_a_recoverable_error(): void
    {
        $user = User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated mail failure'));
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_old_authenticated_session_is_invalid_after_reset(): void
    {
        $user = User::factory()->create();
        $identity = $user->sessionIdentity();
        $this->post(route('password.update'), $this->credentials($user, Password::createToken($user)))
            ->assertRedirect(route('login'));
        $this->withSession([\Illuminate\Support\Facades\Auth::guard()->getName() => $user->id, 'auth_identity' => $identity])
            ->get(route('profile.show'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
