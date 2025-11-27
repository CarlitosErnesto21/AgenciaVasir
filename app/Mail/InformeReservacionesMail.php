<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Empleado;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class InformeReservacionesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $clienteData;
    public $adminData;
    public $companyName;
    public $pdfData;

    public function __construct($clienteData, $pdfData)
    {
        $this->clienteData = $clienteData;
        $this->pdfData = $pdfData;

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
        // Generar el PDF
        $pdf = Pdf::loadView('informes.mis-reservaciones', $this->pdfData)
            ->setPaper('letter', 'portrait');

        $fecha_hora = Carbon::now()->format('Y_m_d_H_i_s');
        $nombreArchivo = "mis_reservaciones_{$fecha_hora}.pdf";

        return $this->subject('Tu Informe de Reservaciones - VASIR')
                    ->view('emails.informe-reservaciones')
                    ->with([
                        'cliente' => $this->clienteData,
                        'companyName' => $this->companyName,
                        'supportEmail' => $this->adminData['email'],
                        'adminData' => $this->adminData,
                        'fecha_generacion' => Carbon::now()->format('d/m/Y H:i:s')
                    ])
                    ->attachData($pdf->output(), $nombreArchivo, [
                        'mime' => 'application/pdf'
                    ]);
    }
}
