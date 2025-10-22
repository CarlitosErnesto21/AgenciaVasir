<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $rescheduleReason;
    public $newDate;
    public $observations;

    public function __construct($reservationData, $clientData, $rescheduleReason, $newDate, $observations = null)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
        $this->rescheduleReason = $rescheduleReason;
        $this->newDate = $newDate;
        $this->observations = $observations;
    }

    public function build()
    {
        return $this->subject('Tu reservación ha sido reprogramada - VASIR Agencia de Viajes')
                    ->view('emails.reservation-rescheduled')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'reason' => $this->rescheduleReason,
                        'newDate' => $this->newDate,
                        'observations' => $this->observations,
                        'companyName' => 'VASIR',
                        'supportEmail' => config('mail.from.address'),
                        'companyPhone' => '+503 7985 8777',
                        'companyAddress' => 'Tu dirección de la agencia',
                    ]);
    }
}