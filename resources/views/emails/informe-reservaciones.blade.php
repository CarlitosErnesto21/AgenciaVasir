<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Reservaciones - {{ $companyName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #7c1d1d;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #7c1d1d;
            margin-bottom: 10px;
        }
        .success-badge {
            background-color: #7c1d1d;
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            margin: 20px 0;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #7c1d1d;
        }
        .client-info {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .contact-info {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        .attachment-note {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
            font-weight: bold;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo" style="text-align: center;">
                <img src="{{ $message->embed(public_path('images/logo_gmail.png')) }}"
                     alt="{{ $companyName }}"
                     style="height: 60px; width: auto; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">
            </div>
            <p>Viajes y Turismo</p>
            <div class="success-badge">
                Informe de Reservaciones Generado
            </div>
        </div>

        <div class="greeting">
            <strong>Estimado/a {{ $cliente['name'] }},</strong>
        </div>

        <div class="info-section">
            <p>Hemos procesado tu solicitud de informe de reservaciones exitosamente.</p>

            <div class="client-info">
                <strong>Detalles del Cliente:</strong><br>
                <strong>Nombre:</strong> {{ $cliente['name'] }}<br>
                <strong>Email:</strong> {{ $cliente['email'] }}<br>
                <strong>Fecha de solicitud:</strong> {{ $fecha_generacion }}
            </div>

            <div class="attachment-note">
                <strong>Archivo Adjunto:</strong> Tu informe completo de reservaciones se encuentra adjunto a este correo en formato PDF.
            </div>
        </div>

        @if($adminData['phone'] || $supportEmail)
        <div class="contact-info">
            <strong>¿Necesitas ayuda?</strong><br>
            Si tienes alguna pregunta sobre tu informe o necesitas asistencia adicional, no dudes en contactarnos:
            <br><br>
            @if($supportEmail)
                <strong>Email:</strong> {{ $supportEmail }}<br>
            @endif
            @if($adminData['phone'])
                <strong>Teléfono:</strong> {{ $adminData['phone'] }}<br>
            @endif
        </div>
        @endif

        <div class="info-section">
            <p><strong>Nota importante:</strong> Este informe ha sido generado de forma segura y contiene información confidencial. Por favor, manténlo en un lugar seguro.</p>
        </div>

        <div class="footer">
            <p>
                <strong>{{ $companyName }}</strong><br>
                2a Calle Oriente casa #12, Chalatenango, El Salvador<br>
                Este es un correo automático, por favor no respondas a esta dirección.
            </p>
        </div>
    </div>
</body>
</html>
