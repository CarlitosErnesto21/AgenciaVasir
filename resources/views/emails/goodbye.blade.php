<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Hasta pronto! - {{ $companyName }}</title>
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
            border-bottom: 3px solid #ff6b6b;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .goodbye-message {
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
        .return-btn {
            display: inline-block;
            background-color: #ff0000;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .return-btn:hover {
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
        .highlight {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
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
            <p style="color: #ff6b6b; font-size: 16px;">¡Hasta pronto, {{ $user->name }}!</p>
        </div>

        <div class="goodbye-message">
            <h2 style="color: #ff6b6b;">😢 Lamentamos verte partir</h2>
            <p>Hola <strong>{{ $user->name }}</strong>, hemos procesado tu solicitud de eliminación de cuenta.</p>
        </div>

        <div class="highlight">
            <h3 style="margin-top: 0; color: #856404;">⚠️ Información importante</h3>
            <p style="margin-bottom: 0;">
                Tu cuenta y todos los datos asociados han sido eliminados permanentemente de nuestros sistemas.
                Esta acción no se puede deshacer.
            </p>
        </div>

        <div class="info-section">
            <h3>💔 ¿Qué significó para nosotros tenerte?</h3>
            <ul>
                <li>🌟 Fuiste parte de nuestra familia de viajeros</li>
                <li>✈️ Confiaste en nosotros para tus aventuras</li>
                <li>🤝 Nos ayudaste a crecer y mejorar</li>
                <li>🎯 Compartiste momentos especiales con nosotros</li>
            </ul>
        </div>

        <div class="info-section">
            <h3>🚪 ¿Cambias de opinión?</h3>
            <p>Si en el futuro decides volver a viajar con nosotros, siempre serás bienvenido/a. Podrás crear una nueva cuenta cuando gustes:</p>
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/register" class="return-btn">
                    Crear nueva cuenta
                </a>
            </div>
        </div>

        <div class="info-section">
            <h3>📝 Tus comentarios son importantes</h3>
            <p>Nos encantaría saber qué podríamos haber hecho mejor. Si tienes algún comentario o sugerencia, no dudes en contactarnos:</p>

            <div style="text-align: center; margin: 20px 0;">
                <a href="mailto:{{ $supportEmail }}" style="text-decoration: none; display: inline-flex; align-items: center; gap: 10px; background-color: #f8f9fa; padding: 15px 25px; border-radius: 8px; border: 2px solid #ff0000;">
                    <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                         alt="Gmail"
                         style="width: 40px; height: 40px; border: none;"
                    >
                </a>
            </div>
        </div>

        <!-- Sección de Contacto y Redes Sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>🌟 ¡Mantente conectado con nosotros!</h3>
            <p>Aunque hayas eliminado tu cuenta, siempre puedes seguirnos para estar al día con nuestras ofertas:</p>
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                📞 <strong>Teléfonos:</strong> <a href="tel:+50379858777" style="color: #ff0000; text-decoration: none;">+503 7985 8777</a> | <a href="tel:+50323279199" style="color: #ff0000; text-decoration: none;">+503 2327 9199</a><br>
                🌐 <strong>Sitio web:</strong> <a href="{{ config('app.url') }}" style="color: #ff0000; text-decoration: none;">{{ config('app.url') }}</a>
            </p>

            <!-- Iconos de redes sociales usando imágenes embebidas -->
            <table align="center" style="margin: 20px auto; border-collapse: collapse;">
                <tr>
                    <td style="padding: 6px; text-align: center;">
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/share/1C7tZxDHzh/" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/facebook-icon.png')) }}"
                                 alt="Facebook"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <!-- Instagram -->
                        <a href="https://www.instagram.com/vasir_sv?igsh=MWx3aGFzdnB5Y2l2OA==" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/instagram-icon.png')) }}"
                                 alt="Instagram"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <!-- TikTok -->
                        <a href="https://www.tiktok.com/@vasir_sv?_t=ZM-8wz8jwve57Y&_r=1" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/tiktok-icon.png')) }}"
                                 alt="TikTok"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <!-- WhatsApp -->
                        <a href="https://wa.me/50379858777" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                 alt="WhatsApp"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                </tr>
            </table>

        <div class="footer">
            <p style="color: #ff6b6b; font-weight: bold;">¡Gracias por haber sido parte de {{ $companyName }}! 💙</p>
            <p><strong>Te deseamos lo mejor en tus futuros viajes 🌍✈️</strong></p>
            <hr>
            <p><small>
                Este correo confirma la eliminación exitosa de tu cuenta. Si no solicitaste esta eliminación,
                contacta inmediatamente a nuestro soporte.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
