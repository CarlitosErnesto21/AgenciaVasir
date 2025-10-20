<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contraseña Actualizada - {{ $companyName }}</title>
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
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .success-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
            background-color: #28a745;
            color: white;
        }
        .message-section {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .success-info {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .change-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-item:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .login-btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 15px 35px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .login-btn:hover {
            background-color: #218838;
        }
        .security-section {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .security-title {
            color: #856404;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .security-text {
            color: #856404;
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
            <div class="success-badge">
                ✅ CONTRASEÑA ACTUALIZADA
            </div>
        </div>

        <div class="message-section">
            <h2>🎉 ¡Contraseña Actualizada Exitosamente!</h2>
            <p>
                Hola <strong>{{ $user->name }}</strong>, tu contraseña ha sido cambiada correctamente
                para la cuenta <strong>{{ $user->email }}</strong>.
            </p>
        </div>

        <div class="success-info">
            <h3 style="color: #155724; margin-top: 0;">✅ Cambio Completado</h3>
            <p style="color: #155724; margin-bottom: 0;">
                Tu nueva contraseña ya está activa y puedes usarla para iniciar sesión.
                Todas las sesiones anteriores han sido cerradas por seguridad.
            </p>
        </div>

        <div class="change-details">
            <h3>📊 Detalles del Cambio</h3>
            <div class="info-item">
                <span class="info-label">👤 Usuario:</span>
                <span class="info-value">{{ $user->name }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">📧 Email:</span>
                <span class="info-value">{{ $user->email }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">🕐 Fecha y Hora:</span>
                <span class="info-value">{{ $changeDetails['timestamp'] ?? now()->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">🌐 Dirección IP:</span>
                <span class="info-value">{{ $changeDetails['ip'] ?? 'No disponible' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">💻 Navegador:</span>
                <span class="info-value">{{ $changeDetails['user_agent'] ?? 'No disponible' }}</span>
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $loginUrl }}" class="login-btn">
                🔑 Iniciar Sesión Ahora
            </a>
        </div>

        <div class="security-section">
            <div class="security-title">🛡️ Recomendaciones de Seguridad</div>
            <div class="security-text">
                <p><strong>Para mantener tu cuenta segura:</strong></p>
                <ul>
                    <li>Usa contraseñas únicas para cada servicio</li>
                    <li>No compartas tu contraseña con nadie</li>
                    <li>Cierra sesión al usar computadoras públicas</li>
                    <li>Revisa regularmente la actividad de tu cuenta</li>
                </ul>
            </div>
        </div>

        <div class="info-section">
            <h3>🚨 ¿No fuiste tú?</h3>
            <p>
                Si NO cambiaste tu contraseña, alguien más puede haber accedido a tu cuenta.
                <strong>Contacta inmediatamente a nuestro soporte:</strong>
            </p>
            <p style="font-size: 14px; color: #666;">
                📞 <strong>Teléfonos:</strong> <a href="tel:+50379858777" style="color: #ff0000;">+503 7985 8777</a> |
                <a href="tel:+50323279199" style="color: #ff0000;">+503 2327 9199</a><br>
                📧 <strong>Email:</strong> <a href="mailto:{{ $supportEmail }}" style="color: #ff0000;">{{ $supportEmail }}</a>
            </p>
        </div>

        <!-- Sección de Redes Sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>🌟 Síguenos en nuestras redes</h3>
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
                        <a href="https://wa.me/50379858777" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                 alt="WhatsApp" style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <a href="mailto:{{ $supportEmail }}" target="_blank" rel="noopener noreferrer">
                            <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                 alt="Gmail" style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p><strong>{{ $companyName }}</strong> - Agencia de Viajes</p>
            <p><small>
                Este correo confirma que tu contraseña fue actualizada.
                Si no realizaste este cambio, contacta a soporte inmediatamente.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
