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

    public function __construct(User $user, $changeDetails = [])
    {
        $this->user = $user;
        $this->changeDetails = $changeDetails;
    }

    public function build()
    {
        return $this->subject('✅ Contraseña Actualizada - VASIR')
                    ->view('emails.password-changed')
                    ->with([
                        'user' => $this->user,
                        'changeDetails' => $this->changeDetails,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                        'loginUrl' => route('login'),
                    ]);
    }
}
