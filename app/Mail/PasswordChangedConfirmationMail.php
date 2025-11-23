<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordChangedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $changeDetails;
    public $adminData;
    public $companyName;

    public function __construct(User $user, $changeDetails = [])
    {
        $this->user = $user;
        $this->changeDetails = $changeDetails;
        $this->companyName = config('app.name', 'VASIR');

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
        return $this->subject('Contraseña Actualizada - ' . $this->companyName)
                    ->view('emails.password-changed')
                    ->with([
                        'user' => $this->user,
                        'changeDetails' => $this->changeDetails,
                        'companyName' => $this->companyName,
                        'supportEmail' => config('mail.from.address'),
                        'loginUrl' => route('login'),
                        'adminData' => $this->adminData,
                    ]);
    }
}
