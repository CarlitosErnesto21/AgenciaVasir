<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - {{ $companyName }}</title>
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
            border-bottom: 3px solid #ff0000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .security-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
            background-color: #dc3545;
            color: white;
        }
        .message-section {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .reset-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .reset-btn {
            display: inline-block;
            background-color: #002fff;
            color: white;
            padding: 15px 35px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .reset-btn:hover {
            background-color: #0225c0;
        }
        .security-section {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .security-title {
            color: #721c24;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .security-text {
            color: #721c24;
            font-size: 14px;
        }
        .info-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .manual-link {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            word-break: break-all;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ $message->embed(public_path('images/logo_gmail.png')) }}"
                     alt="{{ $companyName }}"
                     style="height: 60px; width: auto; margin-bottom: 10px;">
                <div style="font-size: 28px; font-weight: bold; color: #ff0000; margin-top: 10px;"></div>
            </div>
            <p>Tu Agencia de Viajes de Confianza</p>
            <div class="security-badge">
                RECUPERACIÓN DE CONTRASEÑA
            </div>
        </div>

        <div class="message-section">
            <h2>Solicitud de Recuperación de Contraseña</h2>
            <p>
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta
                asociada a <strong>{{ $email }}</strong>.
            </p>
        </div>

        <div class="reset-info">
            <h3 style="color: #856404; margin-top: 0;">Instrucciones Importantes</h3>
            <ul style="color: #856404;">
                <li>Solo usa este enlace si solicitaste el cambio de contraseña</li>
                <li>El enlace expira en <strong>{{ $expirationTime }} minutos</strong></li>
                <li>Si no solicitaste este cambio, ignora este email</li>
                <li>Tu contraseña actual sigue siendo válida hasta que la cambies</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $resetUrl }}" class="reset-btn" style="color: white !important;">
                Restablecer mi Contraseña
            </a>
        </div>

        <div class="security-section">
            <div class="security-title">Medidas de Seguridad</div>
            <div class="security-text">
                <p><strong>Por tu seguridad:</strong></p>
                <ul>
                    <li>Nunca compartir este enlace con nadie</li>
                    <li>Usa una contraseña fuerte y única</li>
                    <li>Si no solicitaste este cambio, contacta inmediatamente a soporte</li>
                    <li>Cierra todas las sesiones abiertas después del cambio</li>
                </ul>
            </div>
        </div>

        <div class="info-section">
            <h3>¿El botón no funciona?</h3>
            <p>Si tienes problemas con el botón, copia y pega este enlace en tu navegador:</p>
            <div class="manual-link">
                {{ $resetUrl }}
            </div>
        </div>

        <div class="info-section">
            <h3>¿Necesitas Ayuda?</h3>
            <p>Si no solicitaste este cambio o tienes problemas, contáctanos:</p>
            <p style="font-size: 14px; color: #666;">
                <strong>Teléfono:</strong> 
                @if(str_contains($adminData['phone'], 'no disponible'))
                    <span style="color: #999; font-style: italic;">{{ $adminData['phone'] }}</span>
                @else
                    <a href="tel:{{ str_replace([' ', '-', '(', ')'], '', $adminData['phone']) }}" style="color: #002fff; text-decoration: none;">{{ $adminData['phone'] }}</a>
                @endif<br>
                <strong>Correo Electrónico:</strong> 
                @if(str_contains($adminData['email'], 'no disponible'))
                    <span style="color: #999; font-style: italic;">{{ $adminData['email'] }}</span>
                @else
                    <a href="mailto:{{ $adminData['email'] }}" style="color: #002fff; text-decoration: none;">{{ $adminData['email'] }}</a>
                @endif<br>
                <strong>Sitio web:</strong> <a href="{{ config('app.url') }}" style="color: #002fff; text-decoration: none;">{{ config('app.url') }}</a>
            </p>
        </div>

        <!-- Sección de Redes Sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>Síguenos en nuestras redes</h3>
            <table align="center" style="margin: 20px auto; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://www.facebook.com/share/1C7tZxDHzh/" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $message->embed(public_path('images/facebook-icon.png')) }}"
                                 alt="Facebook" style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://www.instagram.com/vasir_sv?igsh=MWx3aGFzdnB5Y2x2OA==" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $message->embed(public_path('images/instagram-icon.png')) }}"
                                 alt="Instagram" style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://www.tiktok.com/@vasir_sv?_t=ZM-8wz8jwve57Y&_r=1" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $message->embed(public_path('images/tiktok-icon.png')) }}"
                                 alt="TikTok" style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        @if(str_contains($adminData['phone'], 'no disponible'))
                            <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                 alt="WhatsApp no disponible" style="width: 35px; height: 35px; border: none; opacity: 0.5;">
                        @else
                            <a href="https://wa.me/{{ str_replace([' ', '-', '(', ')', '+'], '', $adminData['phone']) }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                     alt="WhatsApp" style="width: 35px; height: 35px; border: none;">
                            </a>
                        @endif
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        @if(str_contains($adminData['email'], 'no disponible'))
                            <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                 alt="Gmail no disponible" style="width: 35px; height: 35px; border: none; opacity: 0.5;">
                        @else
                            <a href="mailto:{{ $adminData['email'] }}" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                     alt="Gmail" style="width: 35px; height: 35px; border: none;">
                            </a>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p><strong>{{ $companyName }}</strong> - Agencia de Viajes</p>
            <p><small>
                Este correo se envió porque solicitaste restablecer tu contraseña.
                Si no fuiste tú, puedes ignorar este mensaje de forma segura.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
