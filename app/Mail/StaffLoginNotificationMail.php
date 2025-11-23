<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StaffLoginNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $loginDetails;

    public function __construct(User $user, $loginDetails = [])
    {
        $this->user = $user;
        $this->loginDetails = $loginDetails;
    }

    public function build()
    {
        $subject = $this->user->hasRole('Administrador')
            ? 'Inicio de Sesión Administrador - VASIR'
            : 'Inicio de Sesión Empleado - VASIR';

        return $this->subject($subject)
                    ->view('emails.staff-login')
                    ->with([
                        'user' => $this->user,
                        'loginDetails' => $this->loginDetails,
                        'companyName' => 'VASIR',
                        'isAdmin' => $this->user->hasRole('Administrador'),
                        'isEmployee' => $this->user->hasRole('Empleado'),
                        'supportEmail' => config('mail.from.address'),
                    ]);
    }
}
