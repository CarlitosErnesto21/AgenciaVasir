<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationInProgressMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $observations;
    public $adminData;

    public function __construct($reservationData, $clientData, $observations = null)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
        $this->observations = $observations;

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
        return $this->subject('¡Tu aventura ha comenzado! - VASIR Agencia de Viajes')
                    ->view('emails.reservation-inprogress')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'observations' => $this->observations,
                        'companyName' => 'VASIR',
                        'adminData' => $this->adminData,
                        'supportEmail' => $this->adminData['email'],
                        'companyPhone' => $this->adminData['phone'],
                        'companyAddress' => 'Tu dirección de la agencia',
                    ]);
    }
}
