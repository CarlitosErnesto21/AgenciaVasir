<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class GoodbyeUserMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $companyName;
    public $supportEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->companyName = config('app.name', 'VASIR');
        $this->supportEmail = config('mail.from.address', 'vasirtours19@gmail.com');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '¡Hasta pronto! - ' . $this->companyName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.goodbye',
            with: [
                'user' => $this->user,
                'companyName' => $this->companyName,
                'supportEmail' => $this->supportEmail,
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
        return $this->view('emails.goodbye')
                    ->with([
                        'user' => $this->user,
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->supportEmail,
                    ]);
    }
}
