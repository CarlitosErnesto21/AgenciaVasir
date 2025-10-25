<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservación Reprogramada - {{ $companyName }}</title>
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
        .reschedule-badge {
            background-color: #ffc107;
            color: #212529;
            padding: 15px 25px;
            border-radius: 50px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            margin: 20px 0;
        }
        .reservation-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
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
            color: #856404;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .date-change-section {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .date-comparison {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .date-box {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
            margin: 5px;
            min-width: 200px;
        }
        .old-date {
            border: 2px solid #dc3545;
            color: #721c24;
        }
        .new-date {
            border: 2px solid #28a745;
            color: #155724;
        }
        .arrow {
            font-size: 24px;
            color: #856404;
            margin: 0 10px;
        }
        .reason-section {
            background-color: #e7f3ff;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .info-section {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .contact-btn {
            display: inline-block;
            background-color: #ff0000;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }
        .contact-btn:hover {
            background-color: #b30000;
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
            .date-comparison {
                flex-direction: column;
            }
            .arrow {
                transform: rotate(90deg);
                margin: 10px 0;
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
            <p>Tu Agencia de Viajes de Confianza</p>
        </div>

        <!-- Badge de reprogramación -->
        <div style="text-align: center;">
            <div class="reschedule-badge">
                🗓️ RESERVACIÓN REPROGRAMADA
            </div>
        </div>

        <div>
            <h2>¡Hola {{ $client['name'] ?? $client['nombres'] ?? 'Estimado cliente' }}!</h2>
            <p>Te escribimos para informarte que tu reservación ha sido <strong style="color: #856404;">REPROGRAMADA</strong>. 
               A continuación encontrarás todos los detalles de los cambios realizados.</p>
        </div>

        <!-- Detalles de la reservación -->
        <div class="reservation-details">
            <h3 style="margin-top: 0; color: #856404;">📋 Detalles de tu Reservación</h3>
            
            <div class="detail-row">
                <span class="detail-label">🎯 Servicio:</span>
                <span class="detail-value">{{ $reservation['entidad_nombre'] ?? 'N/A' }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">🏷️ Tipo:</span>
                <span class="detail-value" style="text-transform: capitalize;">{{ $reservation['tipo'] ?? 'N/A' }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">👥 Personas:</span>
                <span class="detail-value">
                    {{ ($reservation['mayores_edad'] ?? 0) + ($reservation['menores_edad'] ?? 0) }} personas
                    ({{ $reservation['mayores_edad'] ?? 0 }} adultos, {{ $reservation['menores_edad'] ?? 0 }} niños)
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">💰 Total:</span>
                <span class="detail-value">${{ number_format($reservation['total'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Cambio de fecha -->
        <div class="date-change-section">
            <h3 style="margin-top: 0; color: #856404;">📅 Cambio de Fecha</h3>
            <div class="date-comparison">
                <div class="date-box old-date">
                    <h4 style="margin: 0; font-size: 14px;">FECHA ANTERIOR</h4>
                    <div style="font-size: 16px; font-weight: bold; margin-top: 5px;">
                        {{ date('d/m/Y H:i', strtotime($reservation['fecha_reserva'])) }}
                    </div>
                </div>
                <div class="arrow"></div>
                <div class="date-box new-date">
                    <h4 style="margin: 0; font-size: 14px;">NUEVA FECHA</h4>
                    <div style="font-size: 16px; font-weight: bold; margin-top: 5px;">
                        {{ date('d/m/Y H:i', strtotime($newDate)) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Motivo de la reprogramación -->
        <div class="reason-section">
            <h3 style="margin-top: 0; color: #0c5460;">📝 Motivo de la Reprogramación</h3>
            <p style="margin: 10px 0; font-size: 14px; background-color: white; padding: 15px; border-radius: 5px;">
                {{ $reason }}
            </p>
            @if($observations)
            <h4 style="color: #0c5460; margin-top: 20px;">💬 Observaciones Adicionales</h4>
            <p style="margin: 10px 0; font-size: 14px; background-color: white; padding: 15px; border-radius: 5px;">
                {{ $observations }}
            </p>
            @endif
        </div>

        <!-- Información importante -->
        <div class="info-section">
            <h3 style="margin-top: 0;">ℹ️ Información Importante</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>✅ Tu reservación sigue siendo <strong>válida</strong> para la nueva fecha</li>
                <li>💰 El precio y condiciones permanecen <strong>sin cambios</strong></li>
                <li>📞 Te contactaremos 24-48 horas antes de la nueva fecha</li>
                <li>📧 Guarda este email como <strong>comprobante actualizado</strong></li>
                @if(($reservation['tipo'] ?? '') === 'tours')
                <li>⏰ Recuerda llegar 15 minutos antes al punto de encuentro</li>
                <li>🎒 Lleva documento de identidad y ropa cómoda</li>
                @elseif(($reservation['tipo'] ?? '') === 'hoteles')
                <li>🏨 Las condiciones de check-in y check-out se mantienen</li>
                <li>📋 Presenta este email actualizado en recepción</li>
                @endif
            </ul>
        </div>

        <!-- Botón de contacto -->
        <div style="text-align: center; margin: 30px 0;">
            <p><strong>¿Tienes alguna pregunta sobre los cambios realizados?</strong></p>
            <a href="tel:{{ $companyPhone }}" class="contact-btn">
                📞 Contáctanos
            </a>
        </div>

        <!-- Políticas de cancelación -->
        <div style="background-color: #f1f3f4; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #6c757d;">
            <h4 style="margin-top: 0; color: #495057;">📋 Recordatorio de Políticas</h4>
            <p style="margin: 5px 0; font-size: 13px; color: #6c757d;">
                • Si necesitas cancelar: Contáctanos con al menos 72 horas de anticipación<br>
                • Para cambios adicionales: Sujeto a disponibilidad y posibles cargos<br>
                • Emergencias: Disponible 24/7 en nuestros números de contacto
            </p>
        </div>

        <!-- Sección de contacto y redes sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>🌟 ¡Mantente conectado con nosotros!</h3>
            <p>Síguenos en nuestras redes sociales para más ofertas y destinos:</p>
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                📞 <strong>Teléfonos:</strong> <a href="tel:+50379858777" style="color: #ff0000; text-decoration: none;">+503 7985 8777</a> | <a href="tel:+50323279199" style="color: #ff0000; text-decoration: none;">+503 2327 9199</a><br>
                📧 <strong>Email:</strong> <a href="mailto:{{ $supportEmail }}" style="color: #ff0000; text-decoration: none;">{{ $supportEmail }}</a><br>
                🌐 <strong>Sitio web:</strong> <a href="{{ config('app.url') }}" style="color: #ff0000; text-decoration: none;">{{ config('app.url') }}</a>
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
                    <td style="padding: 6px; text-align: center;">
                        <a href="https://wa.me/50379858777" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                 alt="WhatsApp"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <a href="mailto:{{ $supportEmail }}" target="_blank" rel="noopener noreferrer"
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
            <p>¡Gracias por tu comprensión y paciencia!</p>
            <p><strong>¡Nos vemos en la nueva fecha! 🎉✈️</strong></p>
            <hr>
            <p><small>
                Este correo confirma los cambios en tu reservación. Guárdalo como comprobante actualizado.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>