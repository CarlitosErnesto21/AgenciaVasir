<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Empleado;

class NewReservationStaffMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $tourData;
    public $staffData;
    public $companyName;

    public function __construct($reservationData, $clientData, $tourData, $staffUser)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
        $this->tourData = $tourData;

        // Obtener datos del staff
        $staffEmployee = Empleado::where('user_id', $staffUser->id)->first();
        $this->staffData = [
            'name' => $staffEmployee ? ($staffEmployee->nombres . ' ' . $staffEmployee->apellidos) : $staffUser->name,
            'role' => $staffUser->roles->first()->name ?? 'Empleado'
        ];

        $this->companyName = 'VASIR';
    }

    public function build()
    {
        $subject = 'Nueva Reserva Registrada - VASIR';

        return $this->subject($subject)
                    ->view('emails.new-reservation-staff')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'tour' => $this->tourData,
                        'staff' => $this->staffData,
                        'companyName' => $this->companyName,
                        'supportEmail' => config('mail.from.address'),
                    ]);
    }
}
