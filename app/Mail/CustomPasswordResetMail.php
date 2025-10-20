<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;
    public $resetUrl;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
        
        // Generar URL correcta usando el helper route con parámetros
        $this->resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $email
        ]);
    }

    public function build()
    {
        return $this->subject('🔒 Recuperar Contraseña - VASIR')
                    ->view('emails.password-reset')
                    ->with([
                        'resetUrl' => $this->resetUrl,
                        'email' => $this->email,
                        'token' => $this->token,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                        'expirationTime' => config('auth.passwords.users.expire', 60), // minutos
                    ]);
    }
}
