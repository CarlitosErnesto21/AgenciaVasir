<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $email;
    public $resetUrl;
    public $adminData;
    public $companyName;

    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
        $this->companyName = config('app.name', 'VASIR');

        // Generar URL correcta usando el helper route con parámetros
        $this->resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $email
        ]);

        // Obtener datos del administrador
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'Administrador');
        })->with('empleado')->first();

        $this->adminData = [
            'phone' => $adminUser && $adminUser->empleado && !empty($adminUser->empleado->telefono)
                ? $adminUser->empleado->telefono
                : 'Teléfono no disponible',
            'email' => $adminUser && !empty($adminUser->email)
                ? $adminUser->email
                : 'Email no disponible'
        ];
    }

    public function build()
    {
        return $this->subject('Recuperar Contraseña - ' . $this->companyName)
                    ->view('emails.password-reset')
                    ->with([
                        'resetUrl' => $this->resetUrl,
                        'email' => $this->email,
                        'token' => $this->token,
                        'companyName' => $this->companyName,
                        'supportEmail' => config('mail.from.address'),
                        'expirationTime' => config('auth.passwords.users.expire', 60), // minutos
                        'adminData' => $this->adminData,
                    ]);
    }
}
