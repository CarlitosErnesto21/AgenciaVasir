<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de eliminación de cuenta - {{ $companyName }}</title>
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
            border-bottom: 3px solid #dc3545;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .highlight {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
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
            <p style="color: #dc3545; font-size: 16px;">Notificación Importante</p>
        </div>

        <div class="notification-message">
            <h2 style="color: #dc3545;">Eliminación de Cuenta</h2>
            <p>Estimado/a <strong>{{ $user->name }}</strong>,</p>
            <p>Te informamos que tu cuenta en {{ $companyName }} ha sido eliminada por nuestro equipo administrativo.</p>
        </div>

        <div class="alert-section">
            <h3 style="margin-top: 0; color: #721c24;">Información de la eliminación</h3>
            <p><strong>Fecha de eliminación:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
            <p><strong>Usuario afectado:</strong> {{ $user->name }} ({{ $user->email }})</p>
            <p style="margin-bottom: 0;">
                <strong>Estado:</strong> Tu cuenta y todos los datos asociados han sido eliminados permanentemente de nuestros sistemas.
            </p>
        </div>

        <div class="reason-section">
            <h3 style="margin-top: 0; color: #856404;">Motivo de la eliminación</h3>
            <p style="font-style: italic; background-color: white; padding: 15px; border-radius: 5px; border: 1px solid #e0e0e0; margin-bottom: 0;">
                "{{ $deletionReason }}"
            </p>
        </div>

        <div class="info-section">
            <h3>¿Qué significa esto?</h3>
            <ul>
                <li>Tu cuenta de usuario ha sido eliminada permanentemente</li>
                <li>Todas tus reservas e historial han sido removidos</li>
                <li>Ya no puedes acceder al sistema con tus credenciales anteriores</li>
                <li>Tus datos personales han sido eliminados de nuestra base de datos</li>
            </ul>
        </div>

        <div class="highlight">
            <h3 style="margin-top: 0; color: #004085;">¿Consideras que esto es un error?</h3>
            <p>Si crees que la eliminación de tu cuenta fue un error o deseas obtener más información sobre esta decisión, puedes contactarnos:</p>

            <div style="text-align: center;">
                <a href="mailto:{{ $adminPhones['email'] }}" class="contact-btn" style="color: white !important;">
                    Contactar Soporte
                </a>
            </div>

            <p style="font-size: 14px; color: #666; text-align: center; margin-bottom: 0;">
                Nuestro equipo de soporte revisará tu caso y te proporcionará más detalles si es necesario.
            </p>
        </div>

        <div class="info-section">
            <h3>Datos de contacto</h3>
            <p>Si necesitas comunicarte con nosotros sobre este asunto:</p>

            <div style="text-align: center; margin: 20px 0;">
                <table align="center" style="border-collapse: collapse; margin: 0 auto;">
                    <tr>
                        <td style="padding: 10px; text-align: center; vertical-align: top;">
                            @if(str_contains($adminPhones['email'], 'no disponible'))
                                <div style="display: inline-flex; align-items: center; gap: 10px; background-color: #f8f9fa; padding: 15px 25px; border-radius: 8px; border: 2px solid #ccc; opacity: 0.5;">
                                    <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                         alt="Email no disponible"
                                         style="width: 40px; height: 40px; border: none;">
                                </div>
                            @else
                                <a href="mailto:{{ $adminPhones['email'] }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px; background-color: #f8f9fa; padding: 15px 25px; border-radius: 8px; border: 2px solid #ff0000;">
                                    <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                         alt="Gmail"
                                         style="width: 40px; height: 40px; border: none;"
                                    >
                                </a>
                            @endif
                        </td>
                        <td style="padding: 10px; text-align: center; vertical-align: top;">
                            @if(str_contains($adminPhones['phone1'], 'no disponible'))
                                <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                     alt="WhatsApp no disponible"
                                     style="width: 40px; height: 40px; border: none; opacity: 0.5;">
                            @else
                                <a href="https://wa.me/{{ str_replace([' ', '-', '(', ')', '+'], '', $adminPhones['phone1']) }}" target="_blank" rel="noopener noreferrer"
                                   style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px; background-color: #f8f9fa; padding: 15px 25px; border-radius: 8px; border: 2px solid #ff0000;">
                                    <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                         alt="WhatsApp"
                                         style="width: 40px; height: 40px; border: none;">
                                </a>
                            @endif
                        </td>
                    </tr>
                </table>
                <p style="font-size: 12px; color: #666; margin-top: 8px; font-style: italic;">Contáctanos por email o WhatsApp</p>
            </div>

            <p style="font-size: 14px; color: #666; text-align: center;">
                <strong>Teléfono:</strong>
                @if(str_contains($adminPhones['phone1'], 'no disponible'))
                    <span style="color: #999; font-style: italic;">{{ $adminPhones['phone1'] }}</span>
                @else
                    <a href="tel:{{ str_replace([' ', '-', '(', ')'], '', $adminPhones['phone1']) }}" style="color: #ff0000; text-decoration: none;">{{ $adminPhones['phone1'] }}</a>
                @endif<br>
                <strong>Email:</strong>
                @if(str_contains($adminPhones['email'], 'no disponible'))
                    <span style="color: #999; font-style: italic;">{{ $adminPhones['email'] }}</span>
                @else
                    <a href="mailto:{{ $adminPhones['email'] }}" style="color: #ff0000; text-decoration: none;">{{ $adminPhones['email'] }}</a>
                @endif
            </p>
        </div>

        <div class="info-section">
            <h3>¿Deseas crear una nueva cuenta?</h3>
            <p>Si en el futuro deseas volver a utilizar nuestros servicios, siempre puedes crear una nueva cuenta:</p>
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/register" class="contact-btn" style="color: white !important;">
                    Registrarse nuevamente
                </a>
            </div>
            <p style="font-size: 14px; color: #666; text-align: center; margin-bottom: 0;">
                Ten en cuenta que será necesario proporcionar nuevamente toda la información requerida.
            </p>
        </div>

        <div class="footer">
            <p style="color: #dc3545; font-weight: bold;">Esta es una notificación automática del sistema</p>
            <p><strong>Equipo de {{ $companyName }}</strong></p>
            <hr>
            <p><small>
                Este correo confirma la eliminación administrativa de tu cuenta.
                Si tienes dudas o consideras que es un error, contacta inmediatamente a nuestro soporte.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
