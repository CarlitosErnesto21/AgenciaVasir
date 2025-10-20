<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userData;
    public $verificationUrl;

    public function __construct($userData, $verificationUrl = null)
    {
        // Si es un modelo User, convertir a array
        if ($userData instanceof User) {
            $this->userData = [
                'name' => $userData->name,
                'email' => $userData->email,
            ];
        } else {
            // Si es un array de datos de usuario
            $this->userData = $userData;
        }

        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        // Crear objeto temporal para compatibilidad con la plantilla
        $tempUser = (object) [
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
        ];

        return $this->subject('¡Bienvenido a VASIR - Agencia de Viajes!')
                    ->view('emails.welcome')
                    ->with([
                        'user' => $tempUser,
                        'verificationUrl' => $this->verificationUrl,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                    ]);
    }
}
