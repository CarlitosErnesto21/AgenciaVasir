<?php

namespace App\Providers;

use App\Mail\CustomPasswordResetMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;

class CustomPasswordResetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Personalizar el email de reset de contraseña
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);
        });

        // Personalizar el contenido del email
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new CustomPasswordResetMail($token, $notifiable->email))
                ->to($notifiable->email);
        });
    }
}
