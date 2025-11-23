<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - {{ $companyName }}</title>
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
            border-bottom: 3px solid {{ $isAdmin ? '#dc3545' : '#007bff' }};
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #ff0000;
            margin-bottom: 10px;
        }
        .role-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin: 10px 0;
            background-color: {{ $isAdmin ? '#dc3545' : '#007bff' }};
            color: white;
        }
        .welcome-message {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .login-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid {{ $isAdmin ? '#dc3545' : '#007bff' }};
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
        .dashboard-btn {
            display: inline-block;
            background-color: {{ $isAdmin ? '#002fff' : '#002fff' }};
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
        }
        .dashboard-btn:hover {
            background-color: {{ $isAdmin ? '#0225c0' : '#0225c0' }};
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .icon {
            font-size: 24px;
            margin-right: 8px;
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
            <p>Viajes y Turismo</p>
            <div class="role-badge">
                {{ $isAdmin ? 'ADMINISTRADOR' : 'EMPLEADO' }}
            </div>
        </div>

        <div class="welcome-message">
            <h2>
                ¡Hola {{ $user->name }}!
            </h2>
            <p>
                {{ $isAdmin ? 'Has iniciado sesión como Administrador' : 'Has iniciado sesión como Empleado' }}
                en el sistema VASIR.
            </p>
        </div>

        <div class="login-info">
            <h3>Detalles del Inicio de Sesión</h3>
            <div class="info-item">
                <span class="info-label">Correo electrónico:</span>
                <span class="info-value">{{ $user->email }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Fecha y Hora:</span>
                <span class="info-value">{{ $loginDetails['timestamp'] ?? now()->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Dirección IP:</span>
                <span class="info-value">{{ $loginDetails['ip'] ?? 'No disponible' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Navegador:</span>
                <span class="info-value">{{ $loginDetails['user_agent'] ?? 'No disponible' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Rol:</span>
                <span class="info-value">{{ $isAdmin ? 'Administrador' : 'Empleado' }}</span>
            </div>
        </div>

        @if($isAdmin)
        <div class="security-section">
            <div class="security-title">Acceso de Administrador Detectado</div>
            <div class="security-text">
                <strong>Importante:</strong> Como administrador, tienes acceso completo al sistema.
                Asegúrate de que este inicio de sesión sea legítimo. Si no fuiste tú,
                contacta inmediatamente al equipo de seguridad.
            </div>
        </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/dashboard" class="dashboard-btn" style="color: white !important">
                Ir al Dashboard
            </a>
        </div>

        <div class="login-info">
            <h3>Acciones Disponibles</h3>
            @if($isAdmin)
                <ul style="color: #555; line-height: 1.8;">
                    <li>Gestionar usuarios y empleados</li>
                    <li>Ver reportes y estadísticas completas</li>
                    <li>Configurar el sistema</li>
                    <li>Gestionar hoteles, tours, productos y paquetes de viaje</li>
                    <li>Supervisar ventas, reservas y generar informes de inventario</li>
                    <li>y más...</li>
                </ul>
            @else
                <ul style="color: #555; line-height: 1.8;">
                    <li>Ver reportes y estadísticas completas</li>
                    <li>Configurar el sistema</li>
                    <li>Gestionar hoteles, tours, productos y paquetes de viaje</li>
                    <li>Supervisar ventas, reservas y generar informes de inventario</li>
                    <li>y más...</li>
                </ul>
            @endif
        </div>

        <div class="footer">
            <p><strong>{{ $companyName }}</strong> - Sistema de Gestión</p>
            <p><small>
                Este correo se envía automáticamente por seguridad.
                Si no iniciaste sesión, contacta con el equipo de soporte inmediatamente.
            </small></p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Todos los derechos reservados.</small></p>
        </div>
    </div>
</body>
</html>
