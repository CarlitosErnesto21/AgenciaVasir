<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $rejectionReason;
    public $adminData;

    public function __construct($reservationData, $clientData, $rejectionReason)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
        $this->rejectionReason = $rejectionReason;

        // Obtener datos del administrador
        $adminUser = User::role('Administrador')->first();
        $adminEmployee = $adminUser ? Empleado::where('user_id', $adminUser->id)->first() : null;

        $this->adminData = [
            'email' => $adminUser->email ?? config('mail.from.address'),
            'phone' => $adminEmployee->telefono ?? null,
            'name' => $adminEmployee ? ($adminEmployee->nombres . ' ' . $adminEmployee->apellidos) : 'VASIR Team',
        ];
    }

    public function build()
    {
        return $this->subject('Actualización de tu reservación - VASIR')
                    ->view('emails.reservation-rejected')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'reason' => $this->rejectionReason,
                        'companyName' => 'VASIR',
                        'adminData' => $this->adminData,
                        'supportEmail' => $this->adminData['email'],
                        'companyPhone' => $this->adminData['phone'],
                        'companyAddress' => 'Tu dirección de la agencia',
                    ]);
    }
}
