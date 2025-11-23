<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userData;
    public $verificationUrl;
    public $adminData;
    public $companyName;

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
        $this->companyName = 'VASIR';

        // Obtener datos del administrador - IGUAL que otros emails
        $adminUser = User::role('Administrador')->first();
        $adminEmployee = $adminUser ? Empleado::where('user_id', $adminUser->id)->first() : null;

        $this->adminData = [
            'email' => $adminUser->email ?? config('mail.from.address'),
            'phone' => $adminEmployee->telefono ?? null,
            'name' => $adminEmployee ? 
                (trim(($adminEmployee->nombres ?? '') . ' ' . ($adminEmployee->apellidos ?? '')) ?: ($adminEmployee->nombre ?? $adminUser->name)) : 
                'VASIR',
        ];
    }

    public function build()
    {
        // Crear objeto temporal para compatibilidad con la plantilla
        $tempUser = (object) [
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
        ];

        return $this->subject('¡Bienvenido a VASIR!')
                    ->view('emails.welcome')
                    ->with([
                        'user' => $tempUser,
                        'verificationUrl' => $this->verificationUrl,
                        'companyName' => $this->companyName,
                        'adminData' => $this->adminData,
                        'supportEmail' => $this->adminData['email'],
                    ]);
    }
}
