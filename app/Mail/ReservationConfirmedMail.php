<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmedMail extends Mailable
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
        return $this->subject('¡Tu reservación ha sido confirmada! - VASIR Agencia de Viajes')
                    ->view('emails.reservation-confirmed')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                        'companyPhone' => '+1 (555) 123-4567', // Actualizar con el teléfono real
                        'companyAddress' => 'Tu dirección de la agencia', // Actualizar con la dirección real
                    ]);
    }
}