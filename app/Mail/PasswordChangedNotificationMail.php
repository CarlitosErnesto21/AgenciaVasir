<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Empleado;

class PasswordChangedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $adminData;
    public $changeDateTime;
    public $companyName;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->changeDateTime = now()->format('d/m/Y H:i:s');
        $this->companyName = 'VASIR';

        // Obtener datos del administrador - IGUAL que ReservationConfirmedMail
        $adminUser = User::role('Administrador')->first();
        $adminEmployee = $adminUser ? Empleado::where('user_id', $adminUser->id)->first() : null;

        $this->adminData = [
            'email' => $adminUser->email ?? config('mail.from.address'),
            'phone' => $adminEmployee->telefono ?? null,
            'name' => $adminEmployee ?
                (trim(($adminEmployee->nombres ?? '') . ' ' . ($adminEmployee->apellidos ?? '')) ?: $adminEmployee->nombre ?? $adminUser->name) :
                'VASIR',
        ];
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Contraseña actualizada - VASIR')
                    ->view('emails.password-changed')
                    ->with([
                        'user' => $this->user,
                        'adminData' => $this->adminData,
                        'changeDetails' => [
                            'timestamp' => $this->changeDateTime,
                            'ip' => request()->ip() ?? 'No disponible',
                            'user_agent' => request()->userAgent() ?? 'No disponible'
                        ],
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->adminData['email'],
                        'loginUrl' => route('login'),
                    ]);
    }
}
