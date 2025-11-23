<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Empleado;

class ReservationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $adminData;
    public $companyName;

    public function __construct($reservationData, $clientData)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;

        // Obtener datos del administrador
        $adminUser = User::role('Administrador')->first();
        $adminEmployee = $adminUser ? Empleado::where('user_id', $adminUser->id)->first() : null;

        $this->adminData = [
            'email' => $adminUser->email ?? config('mail.from.address'),
            'phone' => $adminEmployee->telefono ?? null,
            'name' => $adminEmployee ? ($adminEmployee->nombres . ' ' . $adminEmployee->apellidos) : 'VASIR',
        ];

        $this->companyName = 'VASIR';
    }

    public function build()
    {
        return $this->subject('¡Tu reservación ha sido confirmada! - VASIR')
                    ->view('emails.reservation-confirmed')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->adminData['email'],
                        'adminData' => $this->adminData,
                    ]);
    }
}
