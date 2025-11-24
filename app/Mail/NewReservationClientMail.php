<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Empleado;

class NewReservationClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationData;
    public $clientData;
    public $tourData;
    public $adminData;
    public $companyName;

    public function __construct($reservationData, $clientData, $tourData)
    {
        $this->reservationData = $reservationData;
        $this->clientData = $clientData;
        $this->tourData = $tourData;

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
        return $this->subject('¡Reserva Creada Exitosamente! - VASIR')
                    ->view('emails.new-reservation-client')
                    ->with([
                        'reservation' => $this->reservationData,
                        'client' => $this->clientData,
                        'tour' => $this->tourData,
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->adminData['email'],
                        'adminData' => $this->adminData,
                    ]);
    }
}
