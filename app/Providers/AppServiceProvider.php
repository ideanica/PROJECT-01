<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(\App\Services\PaymentSchedule::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin', fn ($user) => $user->isAdmin());
        ResetPassword::toMailUsing(function ($user, string $token) {
            $resetUrl = rtrim(config('app.url'), '/').route('password.reset', [
                'token' => $token, 'email' => $user->getEmailForPasswordReset(),
            ], false);

            return (new MailMessage)
                ->subject('Atur ulang password akun Ocean Paws')
                ->action('Atur ulang password', $resetUrl)
                ->view(['mail.password-reset', 'mail.password-reset-text'], [
                    'customerName' => $user->name,
                    'resetUrl' => $resetUrl,
                    'expiresInMinutes' => config('auth.passwords.users.expire'),
                ]);
        });

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
