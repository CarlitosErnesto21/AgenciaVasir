<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Reservación - {{ $companyName }}</title>
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
        .rejection-badge {
            background-color: #dc3545;
            color: white;
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
            border-left: 4px solid #dc3545;
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
            color: #dc3545;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .reason-section {
            background-color: #ffe6e6;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .info-section {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .warning-section {
            background-color: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
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

        <!-- Badge de rechazo -->
        <div style="text-align: center;">
            <div class="rejection-badge">
                RESERVACIÓN NO CONFIRMADA
            </div>
        </div>

        <div>
            <h2>Hola {{ $client['name'] ?? $client['nombres'] ?? 'Estimado cliente' }},</h2>
            <p>Lamentamos informarte que tu reservación no ha podido ser confirmada en este momento.
               A continuación encontrarás los detalles y el motivo de esta decisión.</p>
        </div>

        <!-- Detalles de la reservación -->
        <div class="reservation-details">
            <h3 style="margin-top: 0; color: #dc3545;">Detalles de la Reservación</h3>

            <div class="detail-row">
                <span class="detail-label">Servicio:</span>
                <span class="detail-value">{{ $reservation['entidad_nombre'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Fecha de salida:</span>
                <span class="detail-value">
                    @if(isset($reservation['fecha_salida']) && $reservation['fecha_salida'])
                        {{ date('d/m/Y H:i', strtotime($reservation['fecha_salida'])) }}
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Cancelada el:</span>
                <span class="detail-value">
                    {{ date('d/m/Y H:i') }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Tipo:</span>
                <span class="detail-value" style="text-transform: capitalize;">{{ $reservation['tipo'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Personas:</span>
                <span class="detail-value">
                    {{ ($reservation['mayores_edad'] ?? 0) + ($reservation['menores_edad'] ?? 0) }} personas
                    ({{ $reservation['mayores_edad'] ?? 0 }} adultos, {{ $reservation['menores_edad'] ?? 0 }} niños)
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Total:</span>
                <span class="detail-value">${{ number_format($reservation['total'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Motivo del rechazo -->
        <div class="reason-section">
            <h3 style="margin-top: 0; color: #721c24;">Motivo de la No Confirmación</h3>
            <p style="margin: 10px 0; font-size: 14px; background-color: white; padding: 15px; border-radius: 5px;">
                {{ $reason }}
            </p>
        </div>

        <!-- Información sobre alternativas -->
        <div class="info-section">
            <h3 style="margin-top: 0;">¿Qué puedes hacer ahora?</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>Contáctanos</strong> para explorar otras opciones disponibles</li>
                <li><strong>Consulta fechas alternativas</strong> para el mismo servicio</li>
                <li><strong>Explora otros destinos</strong> similares que podrían interesarte</li>
                <li><strong>Habla con nuestros expertos</strong> para personalizar tu viaje</li>
                <li><strong>Solicita ser notificado</strong> si se abren nuevos cupos</li>
            </ul>
        </div>

        <!-- Nuestras Acreditaciones -->
        <div class="info-section" style="background-color: #e8f5e8; border-left: 4px solid #28a745;">
            <h3 style="margin-top: 0; color: #155724;">Nuestras Acreditaciones</h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #155724;">
                <li>Somos una marca registrada ®</li>
                <li>Somos una Agencia respaldada por el MITUR y CORSATUR</li>
                <li>Poseemos Sello de Verificación de Protocolos de Bioseguridad</li>
            </ul>
        </div>

        <!-- Políticas de cancelación -->
        <div class="warning-section">
            <h3 style="margin-top: 0; color: #856404;">Políticas de Cancelación</h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #856404;">
                <li>Si por cualquier motivo como organizadores cancelamos el tour, te devolvemos el total de tu dinero</li>
                <li>Si no asistís en la fecha y hora indicada no hay devolución de tu reserva</li>
                <li>Para cancelaciones con menos de 72 horas antes del tour, no hay devolución de tu reserva</li>
            </ul>
        </div>

        <!-- Botón de contacto -->
        <div style="text-align: center; margin: 30px 0;">
            <p><strong>¡No te preocupes! Estamos aquí para ayudarte a encontrar la mejor opción para tu viaje.</strong></p>
            @if($adminData['phone'])
                <a href="tel:{{ $adminData['phone'] }}" class="contact-btn" style="color: white !important">
                    Hablemos Ahora
                </a>
            @else
                <a href="mailto:{{ $adminData['email'] }}" class="contact-btn" style="color: white !important">
                    Contáctanos por Email
                </a>
            @endif
        </div>

        <!-- Sección de contacto y redes sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>¡Mantente conectado con nosotros!</h3>
            <p>Síguenos en nuestras redes sociales para más ofertas y destinos:</p>
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                <strong>Contacto:</strong> {{ $adminData['name'] }}<br>
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
            <p>Gracias por elegirnos, esperamos poder servirte pronto</p>
            <p><strong>¡No te rindas en tus sueños de viajar!</strong></p>
            <hr>
            <p><small>
                Este correo es una notificación automática sobre el estado de tu reservación.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
