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
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .welcome-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .verify-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .verify-btn:hover {
            background-color: #0056b3;
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
            <div class="logo">🌍 {{ $companyName }}</div>
            <p>Tu Agencia de Viajes de Confianza</p>
        </div>

        <div class="welcome-message">
            <h2>¡Hola {{ $user->name }}!</h2>
            <p>¡Bienvenido a <strong>{{ $companyName }}</strong>! Nos emociona tenerte como parte de nuestra familia de viajeros.</p>
        </div>

        @if($verificationUrl)
        <div class="info-section">
            <h3>📧 Verificar tu cuenta</h3>
            <p>Para completar tu registro y acceder a todas nuestras funcionalidades, por favor verifica tu dirección de correo electrónico:</p>
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" class="verify-btn">
                    Verificar mi correo electrónico
                </a>
            </div>
            <p><small>Este enlace expirará en 60 minutos por seguridad.</small></p>
        </div>
        @endif

        <div class="info-section">
            <h3>🎯 ¿Qué puedes hacer ahora?</h3>
            <ul>
                <li>📱 Explorar nuestros destinos increíbles</li>
                <li>✈️ Reservar vuelos al mejor precio</li>
                <li>🏨 Encontrar hoteles de calidad</li>
                <li>🚌 Organizar tours personalizados</li>
                <li>💬 Contactar a nuestro equipo de expertos</li>
            </ul>
        </div>

        <div class="info-section">
            <h3>📞 ¿Necesitas ayuda?</h3>
            <p>Nuestro equipo está aquí para ayudarte:</p>
            <ul>
                <li>📧 Email: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>
                <li>🌐 Sitio web: <a href="{{ config('app.url') }}">{{ config('app.url') }}</a></li>
            </ul>
        </div>

        <div class="footer">
            <p>Gracias por confiar en {{ $companyName }}</p>
            <p><strong>¡Comencemos a planear tu próxima aventura! 🚀</strong></p>
            <hr>
            <p><small>
                Este correo fue enviado automáticamente. Si no creaste esta cuenta, puedes ignorar este mensaje.
            </small></p>
        </div>
    </div>
</body>
</html>
