<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $rejectionReason;

    public function __construct($reservationData, $clientData, $rejectionReason)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
        $this->rejectionReason = $rejectionReason;
    }

    public function build()
    {
        return $this->subject('Actualización de tu reservación - VASIR Agencia de Viajes')
                    ->view('emails.reservation-rejected')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'reason' => $this->rejectionReason,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                        'companyPhone' => '+503 7985 8777',
                        'companyAddress' => 'Tu dirección de la agencia',
                    ]);
    }
}