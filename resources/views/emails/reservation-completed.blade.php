<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Experiencia Finalizada! - {{ $companyName }}</title>
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
            border-bottom: 3px solid #6f42c1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .completed-badge {
            background: linear-gradient(135deg, #6f42c1, #8e44ad);
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
            border-left: 4px solid #6f42c1;
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
            color: #6f42c1;
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
        }
        .detail-value {
            color: #6c757d;
        }
        .feedback-section {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
            text-align: center;
        }
        .info-section {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .highlight-box {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
            text-align: center;
        }
        .feedback-btn {
            display: inline-block;
            background: linear-gradient(135deg, #ffc107, #f39c12);
            color: #212529;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            margin: 10px 0;
            font-weight: bold;
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        .feedback-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
        .star-rating {
            font-size: 24px;
            color: #ffc107;
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
            <p>Tu Agencia de Viajes de Confianza</p>
        </div>

        <!-- Badge de finalización -->
        <div style="text-align: center;">
            <div class="completed-badge">
                🎉 ¡Experiencia Finalizada!
            </div>
        </div>

        <div>
            <h2>¡Hola {{ $client['name'] ?? $client['nombres'] ?? 'Estimado cliente' }}!</h2>
            <p>Esperamos que hayas disfrutado al máximo tu experiencia con nosotros. 
               Tu reservación ha sido <strong style="color: #6f42c1;">FINALIZADA</strong> exitosamente.</p>
        </div>

        <!-- Detalles de la experiencia -->
        <div class="reservation-details">
            <h3 style="margin-top: 0; color: #6f42c1;">🎯 Resumen de tu Experiencia</h3>
            
            <div class="detail-row">
                <span class="detail-label">🎯 Servicio:</span>
                <span class="detail-value">{{ $reservation['entidad_nombre'] ?? 'N/A' }}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">📅 Fecha:</span>
                <span class="detail-value">{{ date('d/m/Y H:i', strtotime($reservation['fecha_reserva'])) }}</span>
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

        <!-- Sección de feedback destacada -->
        <div class="feedback-section">
            <h3 style="margin-top: 0; color: #856404;">⭐ ¡Tu Opinión es Muy Importante!</h3>
            <div class="star-rating">⭐⭐⭐⭐⭐</div>
            <p style="margin: 15px 0; font-size: 16px;">
                <strong>¿Cómo fue tu experiencia con nosotros?</strong><br>
                Tu feedback nos ayuda a mejorar y brindar mejores experiencias a futuros viajeros.
            </p>
            <a href="mailto:{{ $supportEmail }}?subject=Feedback - {{ $reservation['entidad_nombre'] ?? 'Mi experiencia' }}&body=Hola, quiero compartir mi experiencia sobre:" class="feedback-btn">
                🌟 Déjanos tu Reseña
            </a>
        </div>

        <!-- Ofertas especiales -->
        <div class="highlight-box">
            <h3 style="margin-top: 0; color: #155724;">🎁 ¡Oferta Especial para Ti!</h3>
            <p style="margin: 10px 0; font-size: 16px; color: #155724;">
                <strong>Como agradecimiento, tienes un 15% de descuento en tu próxima reservación</strong><br>
                <small>Válido por 60 días. Contacta con nosotros y menciona este email.</small>
            </p>
        </div>

        <!-- Información útil -->
        <div class="info-section">
            <h3 style="margin-top: 0;">💡 ¿Qué puedes hacer ahora?</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>📸 <strong>Comparte tus fotos</strong> en redes sociales y etiquétanos</li>
                <li>⭐ <strong>Déjanos una reseña</strong> en Google o Facebook</li>
                <li>👨‍👩‍👧‍👦 <strong>Recomiéndanos</strong> a tus familiares y amigos</li>
                <li>🎯 <strong>Explora nuevos destinos</strong> en nuestro catálogo</li>
                <li>📧 <strong>Suscríbete</strong> a nuestro newsletter para ofertas exclusivas</li>
                <li>💬 <strong>Síguenos</strong> en redes sociales para más aventuras</li>
            </ul>
        </div>

        <!-- Botón de contacto -->
        <div style="text-align: center; margin: 30px 0;">
            <p><strong>¿Quieres planear tu próxima aventura?</strong></p>
            <a href="tel:+50379858777" class="contact-btn">
                📞 ¡Contáctanos para Más Aventuras!
            </a>
        </div>

        <!-- Programa de lealtad -->
        <div style="background-color: #f1f3f4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #6c757d; text-align: center;">
            <p style="margin: 10px 0; font-size: 14px; color: #6c757d;">
                Con cada reservación acumulas puntos para descuentos futuros.<br>
                <strong>¡Pregúntanos sobre nuestro programa de lealtad!</strong>
            </p>
        </div>

        <!-- Sección de contacto y redes sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>🌟 ¡Síguenos y mantente conectado!</h3>
            <p>No te pierdas nuestras ofertas especiales y nuevos destinos:</p>
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
            <p>¡Gracias por elegirnos para tu aventura!</p>
            <p><strong>¡Esperamos verte pronto en tu próximo viaje! 🌍✈️🎉</strong></p>
            <hr>
            <p><small>
                Este correo confirma la finalización exitosa de tu experiencia con nosotros.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>