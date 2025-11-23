<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a {{ $companyName }}</title>
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
        .welcome-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .verify-btn {
            display: inline-block;
            background-color: #002fff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .verify-btn:hover {
            background-color: #0225c0;
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
            <div class="logo" style="text-align: center;">
                <img src="{{ $message->embed(public_path('images/logo_gmail.png')) }}"
                     alt="{{ $companyName }}"
                     style="height: 60px; width: auto; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">

                <div style="font-size: 28px; font-weight: bold; color: #ff0000; margin-top: 10px;"></div>
            </div>
            <p>Viajes y Turismo</p>
        </div>

        <div class="welcome-message">
            <h2>¡Hola {{ $user->name }}!</h2>
            <p>¡Bienvenido a <strong>{{ $companyName }}</strong>! Nos emociona tenerte como parte de nuestra familia de viajeros.</p>
        </div>

        @if($verificationUrl)
        <div class="info-section">
            <h3>Verificar tu cuenta</h3>
            <p>Para completar tu registro y acceder a todas nuestras funcionalidades, por favor verifica tu dirección de correo electrónico:</p>
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-btn" style="color: white !important">
                    Verificar mi correo electrónico
                </a>
            </div>
            <p><small>Este enlace expirará en 15 minutos por seguridad.</small></p>
        </div>
        @endif

        <div class="info-section">
            <h3>¿Qué puedes hacer ahora?</h3>
            <ul>
                <li>Explorar nuestros destinos increíbles</li>
                <li>Reservar vuelos al mejor precio</li>
                <li>Encontrar hoteles de calidad</li>
                <li>Comprar productos y paquetes de viaje</li>
                <li>Y mucho más...</li>
            </ul>
        </div>

        <!-- Sección de Contacto y Redes Sociales -->
        <div class="info-section" style="text-align: center;">
            <h3>¡Mantente conectado con nosotros!</h3>
            <p>Síguenos en nuestras redes sociales y contáctanos directamente:</p>
            <p style="font-size: 14px; color: #666; margin-bottom: 20px;">
                @if($adminData['phone'])
                    <strong>Teléfono:</strong> <a href="tel:{{ str_replace([' ', '-', '(', ')'], '', $adminData['phone']) }}" style="color: #ff0000; text-decoration: none;">{{ $adminData['phone'] }}</a><br>
                @endif
                <strong>Correo Electrónico:</strong> <a href="mailto:{{ $adminData['email'] }}" style="color: #ff0000; text-decoration: none;">{{ $adminData['email'] }}</a><br>
                <strong>Sitio web:</strong> <a href="{{ config('app.url') }}" style="color: #ff0000; text-decoration: none;">{{ config('app.url') }}</a>
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
                        <a href="https://www.instagram.com/vasir_sv?igsh=MWx3aGFzdnB5Y2x2OA==" target="_blank" rel="noopener noreferrer"
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
                        @if($adminData['phone'])
                            <a href="https://wa.me/{{ str_replace([' ', '-', '(', ')', '+'], '', $adminData['phone']) }}" target="_blank" rel="noopener noreferrer"
                               style="text-decoration: none;">
                                <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                     alt="WhatsApp"
                                     style="width: 35px; height: 35px; border: none;">
                            </a>
                        @else
                            <img src="{{ $message->embed(public_path('images/whatsapp-icon.png')) }}"
                                 alt="WhatsApp no disponible"
                                 style="width: 35px; height: 35px; border: none; opacity: 0.5;">
                        @endif
                    </td>
                    <td style="padding: 6px; text-align: center;">
                        <!-- Gmail -->
                        <a href="mailto:{{ $adminData['email'] }}" target="_blank" rel="noopener noreferrer"
                           style="text-decoration: none;">
                            <img src="{{ $message->embed(public_path('images/gmail-icon.png')) }}"
                                 alt="Gmail"
                                 style="width: 35px; height: 35px; border: none;">
                        </a>
                    </td>
                </tr>
            </table>
            <div class="footer">
            <p>Gracias por confiar en {{ $companyName }}</p>
            <p><strong>¡Comencemos a planear tu próxima aventura!</strong></p>
            <hr>
            <p><small>
                Este correo fue enviado automáticamente. Si no creaste esta cuenta, puedes ignorar este mensaje.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
