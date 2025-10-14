<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationUrl;

    public function __construct(User $user, $verificationUrl = null)
    {
        $this->user = $user;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        return $this->subject('¡Bienvenido a VASIR - Agencia de Viajes!')
                    ->view('emails.welcome')
                    ->with([
                        'user' => $this->user,
                        'verificationUrl' => $this->verificationUrl,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                    ]);
    }
}