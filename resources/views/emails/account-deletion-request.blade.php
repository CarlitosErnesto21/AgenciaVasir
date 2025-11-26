<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de eliminación de cuenta - {{ $companyName }}</title>
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
            border-bottom: 3px solid #ffc107;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .notification-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .alert-section {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .reason-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .contact-btn {
            display: inline-block;
            background-color: #002fff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .contact-btn:hover {
            background-color: #0225c0;
        }
        .notification-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
            text-align: center;
        }
        .highlight {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
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

                <div style="font-size: 28px; font-weight: bold; color: #ff0000; margin-top: 10px;"></div>
            </div>
            <p>Viajes y Turismo</p>
            <p style="color: #ffc107; font-size: 16px;">Solicitud Administrativa</p>
        </div>

        <div class="notification-message">
            <h2 style="color: #856404;">Solicitud de Eliminación de Cuenta</h2>
            <p>Estimado/a <strong>Administrador</strong>,</p>
            <p>Te notificamos que el cliente <strong>{{ $user->name }}</strong> ha solicitado la eliminación de su cuenta en {{ $companyName }}.</p>
        </div>

        <div class="reason-section">
            <h3 style="margin-top: 0; color: #856404;">Detalles de la Solicitud</h3>
            <div style="background-color: white; padding: 15px; border-radius: 5px; border: 1px solid #e0e0e0;">
                <p><strong>Nombre del Cliente:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Rol:</strong> Cliente</p>
                <p><strong>Fecha de solicitud:</strong> {{ $requestDate }}</p>
                <p style="margin-bottom: 0;"><strong>ID de usuario:</strong> #{{ $user->id }}</p>
            </div>
        </div>

        <div class="highlight">
            <h3 style="margin-top: 0; color: #004085;">Acciones Requeridas</h3>
            <p>Como administrador, debes revisar esta solicitud y tomar una de las siguientes acciones:</p>

            <ul>
                <li><strong>Revisar la cuenta del usuario:</strong> Verifica si hay reservas pendientes, ventas en proceso o cualquier otro dato importante.</li>
                <li><strong>Contactar al usuario (opcional):</strong> Si necesitas confirmar la solicitud o resolver algún asunto pendiente.</li>
                <li style="margin-bottom: 0;"><strong>Procesar la eliminación:</strong> Si decides eliminar la cuenta, hazlo desde el panel de administración.</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/gestion-usuarios" class="contact-btn" style="color: white !important; margin-right: 10px;">
                Ir al Panel de Administración
            </a>
            <a href="mailto:{{ $user->email }}" class="contact-btn" style="color: white !important; background-color: #dc3545;">
                Contactar al Usuario
            </a>
        </div>

        <div class="alert-section">
            <h3 style="margin-top: 0; color: #721c24;">Advertencia Importante</h3>
            <ul>
                <li>La eliminación de cuenta es una acción <strong>irreversible</strong></li>
                <li>Se eliminarán todos los datos del usuario, incluyendo reservas e historial</li>
                <li>Se enviará una notificación de confirmación al usuario</li>
                <li style="margin-bottom: 0;">Asegúrate de resolver cualquier asunto pendiente antes de proceder</li>
            </ul>
        </div>

        <div class="info-section">
            <h3>Información Adicional</h3>
            <p>
                Esta solicitud fue generada automáticamente cuando el usuario {{ $user->name }}
                seleccionó la opción de eliminar cuenta desde su perfil.
            </p>
            <p style="margin-bottom: 0;">
                <strong>Tiempo de respuesta recomendado:</strong> 24-48 horas
            </p>
        </div>

        <div class="footer">
            <p><strong>{{ $companyName }}</strong> - Agencia de Viajes</p>
            <p><small>
                Este correo notifica una solicitud de eliminación de cuenta.
                Procesa la solicitud desde el panel de administración.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
