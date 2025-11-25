<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AccountDeletedByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $deletionReason;
    public $companyName;
    public $supportEmail;
    public $adminPhones;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $deletionReason = null)
    {
        $this->user = $user;
        $this->deletionReason = $deletionReason ?? 'No se especificó un motivo';
        $this->companyName = config('app.name', 'VASIR');
        $this->supportEmail = config('mail.from.address', 'vasirtours19@gmail.com');

        // Obtener teléfonos del administrador
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'Administrador');
        })->with('empleado')->first();

        $this->adminPhones = [
            'phone1' => $adminUser && $adminUser->empleado && !empty($adminUser->empleado->telefono)
                ? $adminUser->empleado->telefono
                : 'Teléfono no disponible',
            'website' => config('app.url', 'Sitio web no disponible'),
            'email' => $adminUser && !empty($adminUser->email)
                ? $adminUser->email
                : 'Email no disponible'
        ];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Notificación de eliminación de cuenta - ' . $this->companyName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-deleted-by-admin',
            with: [
                'user' => $this->user,
                'deletionReason' => $this->deletionReason,
                'companyName' => $this->companyName,
                'supportEmail' => $this->supportEmail,
                'adminPhones' => $this->adminPhones,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Notificación de eliminación de cuenta - ' . $this->companyName)
                    ->view('emails.account-deleted-by-admin')
                    ->with([
                        'user' => $this->user,
                        'deletionReason' => $this->deletionReason,
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->supportEmail,
                        'adminPhones' => $this->adminPhones,
                    ]);
    }
}
