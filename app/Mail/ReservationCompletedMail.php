<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;

    public function __construct($reservationData, $clientData)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
    }

    public function build()
    {
        return $this->subject('¡Tu experiencia ha finalizado! - VASIR Agencia de Viajes')
                    ->view('emails.reservation-completed')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                        'companyPhone' => '+503 7985 8777',
                        'companyAddress' => 'Tu dirección de la agencia',
                    ]);
    }
}