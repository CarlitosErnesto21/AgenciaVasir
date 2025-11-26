<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class AccountDeletionRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $companyName;
    public $supportEmail;
    public $adminEmail;
    public $requestDate;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->companyName = config('app.name', 'VASIR');
        $this->supportEmail = config('mail.from.address', 'vasirtours19@gmail.com');
        $this->requestDate = now()->format('d/m/Y H:i:s');

        // Obtener el email del administrador
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'Administrador');
        })->first();

        $this->adminEmail = $adminUser ? $adminUser->email : 'admin@vasir.com';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de eliminación de cuenta - ' . $this->user->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account-deletion-request',
            with: [
                'user' => $this->user,
                'companyName' => $this->companyName,
                'supportEmail' => $this->supportEmail,
                'adminEmail' => $this->adminEmail,
                'requestDate' => $this->requestDate,
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
        return $this->subject('Solicitud de eliminación de cuenta - ' . $this->user->name)
                    ->view('emails.account-deletion-request')
                    ->with([
                        'user' => $this->user,
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->supportEmail,
                        'adminEmail' => $this->adminEmail,
                        'requestDate' => $this->requestDate,
                    ]);
    }
}