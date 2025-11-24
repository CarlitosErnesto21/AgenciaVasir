<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Tu Aventura Ha Comenzado! - {{ $companyName }}</title>
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
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .in-progress-badge {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            margin: 20px 0;
            text-transform: uppercase;
        }
        .reservation-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .detail-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 16px;
            color: #2563eb;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .instructions-section {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
        }
        .emergency-section {
            background-color: #fef3c7;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #f59e0b;
        }
        .info-section {
            background-color: #e0f7fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #0891b2;
        }
        .contact-btn {
            display: inline-block;
            background-color: #002fff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
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
        @media only screen and (max-width: 600px) {
            .detail-row {
                flex-direction: column;
            }
            .detail-label, .detail-value {
                text-align: left;
            }
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
        </div>

        <!-- Badge de en curso -->
        <div style="text-align: center;">
            <div class="in-progress-badge">
                ¡Tu Aventura Ha Comenzado!
            </div>
        </div>

        <div>
            <h2>¡Hola {{ $client['name'] ?? $client['nombres'] ?? 'Estimado viajero' }}!</h2>
            <p>¡Excelentes noticias! Tu tour ha comenzado y está actualmente
               <strong style="color: #2563eb;">EN CURSO</strong>. ¡Esperamos que disfrutes cada momento de esta increíble experiencia!</p>
        </div>

        <!-- Detalles del tour en curso -->
        <div class="reservation-details">
            <h3 style="margin-top: 0; color: #2563eb;">Tu Aventura Actual</h3>

            <div class="detail-row">
                <span class="detail-label">Tour: </span>
                <span class="detail-value">{{ $reservation['entidad_nombre'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Iniciado el: </span>
                <span class="detail-value">
                    {{ date('d/m/Y H:i') }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Categoría: </span>
                <span class="detail-value" style="text-transform: capitalize;">{{ $reservation['categoria'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Personas: </span>
                <span class="detail-value">
                    {{ ($reservation['mayores_edad'] ?? 0) + ($reservation['menores_edad'] ?? 0) }} personas
                    ({{ $reservation['mayores_edad'] ?? 0 }} adultos, {{ $reservation['menores_edad'] ?? 0 }} niños)
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Total: </span>
                <span class="detail-value">${{ number_format($reservation['total'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Instrucciones importantes -->
        <div class="instructions-section">
            <h3 style="margin-top: 0; color: #1e40af;">Instrucciones Importantes</h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #1e40af;">
                <li><strong>Mantente atento</strong> a las indicaciones de tu guía turístico</li>
                <li><strong>Conserva este correo</strong> como comprobante de tu participación</li>
                <li><strong>Sigue las medidas de seguridad</strong> establecidas por el equipo</li>
                <li><strong>Disfruta la experiencia</strong> y toma muchas fotos</li>
                <li><strong>Permanece con el grupo</strong> en todo momento</li>
            </ul>
        </div>

        <!-- Información de emergencia -->
        <div class="emergency-section">
            <h3 style="margin-top: 0; color: #92400e;">Contacto de Emergencia</h3>
            <p style="margin: 10px 0; color: #92400e;">
                <strong>En caso de cualquier emergencia o consulta durante el tour:</strong>
            </p>
            <ul style="margin: 10px 0; padding-left: 20px; color: #92400e;">
                @if($adminData['phone'])
                    <li><strong>Teléfono:</strong> <a href="tel:{{ $adminData['phone'] }}" style="color: #92400e; text-decoration: none;">{{ $adminData['phone'] }}</a></li>
                    <li><strong>WhatsApp:</strong> <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $adminData['phone']) }}" target="_blank" style="color: #92400e; text-decoration: none;">{{ $adminData['phone'] }}</a></li>
                @endif
                <li><strong>Email:</strong> <a href="mailto:{{ $adminData['email'] }}" style="color: #92400e; text-decoration: none;">{{ $adminData['email'] }}</a></li>
            </ul>
        </div>

        <!-- Información útil -->
        <div class="info-section">
            <h3 style="margin-top: 0; color: #0e7490;">Consejos para tu Experiencia</h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #0e7490;">
                <li><strong>Hidrátate constantemente</strong> especialmente en climas cálidos</li>
                <li><strong>Usa protector solar</strong> y lleva ropa cómoda</li>
                <li><strong>Mantén tus pertenencias seguras</strong> en todo momento</li>
                <li><strong>Respeta el medio ambiente</strong> y las comunidades locales</li>
                <li><strong>Pregunta</strong> si tienes dudas sobre cualquier actividad</li>
            </ul>
        </div>

        <!-- Botón de contacto -->
        <div style="text-align: center; margin: 30px 0;">
            <p><strong>¿Necesitas ayuda durante el tour?</strong></p>
            @if($adminData['phone'])
                <a href="tel:{{ $adminData['phone'] }}" class="contact-btn" style="color: white !important">
                    Contactar Emergencia
                </a>
            @else
                <a href="mailto:{{ $adminData['email'] }}" class="contact-btn" style="color: white !important">
                    Contactar Soporte
                </a>
            @endif
        </div>

        <!-- Sección de contacto y redes sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>¡Comparte tu Experiencia!</h3>
            <p>Etiquétanos en tus fotos y stories para compartir tu aventura con otros viajeros:</p>
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                @if($adminData['phone'])
                    <strong>Teléfono:</strong> <a href="tel:{{ $adminData['phone'] }}" style="color: #ff0000; text-decoration: none;">{{ $adminData['phone'] }}</a><br>
                @endif
                <strong>Email:</strong> <a href="mailto:{{ $adminData['email'] }}" style="color: #ff0000; text-decoration: none;">{{ $adminData['email'] }}</a><br>
                <strong>Sitio web:</strong> <a href="{{ config('app.url') }}" style="color: #ff0000; text-decoration: none;">{{ config('app.url') }}</a>
            </p>

            <!-- Iconos de redes sociales -->
            <table align="center" style="margin: 20px auto; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://www.facebook.com/share/1C7tZxDHzh/" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/facebook-icon.png')) }}"
                                 alt="Facebook"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://www.instagram.com/vasir_sv?igsh=MWx3aGFzdnB5Y2x2OA==" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/instagram-icon.png')) }}"
                                 alt="Instagram"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://www.tiktok.com/@vasir_sv?_t=ZM-8wz8jwve57Y&_r=1" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/tiktok-icon.png')) }}"
                                 alt="TikTok"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    @if($adminData['phone'])
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $adminData['phone']) }}" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                 alt="WhatsApp"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    @endif
                    <td style="padding: 6px; text-align: center;">
                        <a href="mailto:{{ $adminData['email'] }}" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                 alt="Gmail"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>¡Disfruta tu aventura al máximo!</p>
            <p><strong>¡Que tengas una experiencia inolvidable!</strong></p>
            <hr>
            <p><small>
                Este correo confirma que tu tour está actualmente en progreso.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
