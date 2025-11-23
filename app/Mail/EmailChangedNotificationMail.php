<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Empleado;

class EmailChangedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $oldEmail;
    public $newEmail;
    public $adminData;
    public $changeDateTime;
    public $companyName;
    public $isOldEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $oldEmail, $newEmail, $isOldEmail = false)
    {
        $this->user = $user;
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
        $this->isOldEmail = $isOldEmail;
        $this->changeDateTime = now()->format('d/m/Y H:i:s');
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

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Email actualizado - VASIR')
                    ->view('emails.email-changed')
                    ->with([
                        'user' => $this->user,
                        'oldEmail' => $this->oldEmail,
                        'newEmail' => $this->newEmail,
                        'isOldEmail' => $this->isOldEmail,
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
