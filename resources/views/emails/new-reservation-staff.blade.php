<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Reserva Registrada - {{ $companyName }}</title>
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
        .alert-badge {
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
        .client-details {
            background-color: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .tour-details {
            background-color: #f0f8ff;
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
        }
        .detail-label {
            font-weight: bold;
            color: #495057;
            min-width: 120px;
        }
        .detail-value {
            color: #6c757d;
            flex: 1;
            text-align: right;
        }
        .action-section {
            background-color: #fff3cd;
            padding: 20px;
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
        .urgent-btn {
            display: inline-block;
            background-color: #dc3545;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        .priority {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
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

        <!-- Badge de alerta -->
        <div style="text-align: center;">
            <div class="alert-badge">
                NUEVA RESERVA REGISTRADA
            </div>
        </div>

        <div>
            <h2>¡Hola {{ $staff['name'] }}! <span class="priority">{{ strtoupper($staff['role']) }}</span></h2>
            <p>Se ha registrado una <strong style="color: #dc3545;">nueva reserva</strong> en el sistema que requiere tu atención para proceder con la confirmación.</p>
        </div>

        <!-- Detalles de la reserva -->
        <div class="reservation-details">
            <h3 style="margin-top: 0; color: #dc3545;">Detalles de la Reserva</h3>

            <div class="detail-row">
                <span class="detail-label">ID Reserva:</span>
                <span class="detail-value">#{{ $reservation['id'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Fecha registro:</span>
                <span class="detail-value">
                    @if(isset($reservation['created_at']))
                        {{ \Carbon\Carbon::parse($reservation['created_at'])->format('d/m/Y H:i') }}
                    @else
                        {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Estado:</span>
                <span class="detail-value" style="color: #856404; font-weight: bold;">{{ $reservation['estado'] ?? 'PENDIENTE' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Adultos:</span>
                <span class="detail-value">{{ $reservation['mayores_edad'] ?? 0 }} personas</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Niños:</span>
                <span class="detail-value">{{ $reservation['menores_edad'] ?? 0 }} personas</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Total personas:</span>
                <span class="detail-value" style="font-weight: bold;">{{ ($reservation['mayores_edad'] ?? 0) + ($reservation['menores_edad'] ?? 0) }} personas</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Precio total:</span>
                <span class="detail-value" style="font-weight: bold; color: #28a745;">${{ number_format($reservation['precio_total'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Información del cliente -->
        <div class="client-details">
            <h3 style="margin-top: 0; color: #007bff;">Información del Cliente</h3>

            <div class="detail-row">
                <span class="detail-label">Nombre:</span>
                <span class="detail-value">{{ $client['nombre_completo'] ?? ($client['nombres'] . ' ' . ($client['apellidos'] ?? '')) }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Documento:</span>
                <span class="detail-value">{{ $client['tipo_documento'] ?? 'N/A' }}: {{ $client['numero_identificacion'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Teléfono:</span>
                <span class="detail-value">
                    <a href="tel:{{ $client['telefono'] ?? '' }}" style="color: #007bff; text-decoration: none;">
                        {{ $client['telefono'] ?? 'N/A' }}
                    </a>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">
                    <a href="mailto:{{ $client['email'] ?? '' }}" style="color: #007bff; text-decoration: none;">
                        {{ $client['email'] ?? 'N/A' }}
                    </a>
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Género:</span>
                <span class="detail-value">{{ $client['genero'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Fecha de nacimiento:</span>
                <span class="detail-value">
                    @if(isset($client['fecha_nacimiento']) && $client['fecha_nacimiento'])
                        {{ \Carbon\Carbon::parse($client['fecha_nacimiento'])->format('d/m/Y') }}
                        ({{ \Carbon\Carbon::parse($client['fecha_nacimiento'])->age }} años)
                    @else
                        N/A
                    @endif
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Dirección:</span>
                <span class="detail-value">{{ $client['direccion'] ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Información del tour -->
        <div class="tour-details">
            <h3 style="margin-top: 0; color: #6f42c1;">Información del Tour</h3>

            <div class="detail-row">
                <span class="detail-label">Nombre:</span>
                <span class="detail-value" style="font-weight: bold;">{{ $tour['nombre'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Categoría:</span>
                <span class="detail-value" style="text-transform: capitalize;">{{ $tour['categoria'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Estado del tour:</span>
                <span class="detail-value" style="font-weight: bold; color: #28a745;">{{ $tour['estado'] ?? 'N/A' }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Fecha de salida:</span>
                <span class="detail-value">
                    @if(isset($tour['fecha_salida']) && $tour['fecha_salida'])
                        {{ \Carbon\Carbon::parse($tour['fecha_salida'])->format('d/m/Y H:i') }}
                    @else
                        N/A
                    @endif
                </span>
            </div>

            @if(isset($tour['fecha_regreso']) && $tour['fecha_regreso'])
            <div class="detail-row">
                <span class="detail-label">Fecha de regreso:</span>
                <span class="detail-value">
                    {{ \Carbon\Carbon::parse($tour['fecha_regreso'])->format('d/m/Y H:i') }}
                </span>
            </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Cupo mínimo:</span>
                <span class="detail-value">{{ $tour['cupo_min'] ?? 'N/A' }} personas</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Cupo máximo:</span>
                <span class="detail-value">{{ $tour['cupo_max'] ?? 'N/A' }} personas</span>
            </div>

            @if(isset($tour['cupos_disponibles']))
            <div class="detail-row">
                <span class="detail-label">Cupos disponibles:</span>
                <span class="detail-value" style="font-weight: bold; color: {{ $tour['cupos_disponibles'] > 0 ? '#28a745' : '#dc3545' }};">
                    {{ $tour['cupos_disponibles'] }} personas
                </span>
            </div>
            @endif

            <div class="detail-row">
                <span class="detail-label">Precio por persona:</span>
                <span class="detail-value">${{ number_format($tour['precio'] ?? 0, 2) }}</span>
            </div>


        </div>

        <!-- Sección de acciones -->
        <div class="action-section">
            <h3 style="margin-top: 0; color: #856404;">Acciones Requeridas</h3>
            <ul style="margin: 10px 0; padding-left: 20px; color: #856404;">
                <li><strong>Revisar</strong> los datos de la reserva y del cliente</li>
                <li><strong>Verificar</strong> la disponibilidad del tour</li>
                <li><strong>Contactar</strong> al cliente para confirmar detalles si es necesario</li>
                <li><strong>Proceder</strong> con la confirmación o rechazo de la reserva</li>
                <li><strong>Actualizar</strong> el estado en el sistema administrativo</li>
            </ul>
        </div>

        <!-- Botones de acción -->
        <div style="text-align: center; margin: 30px 0;">
            <p><strong>Acceso directo al cliente:</strong></p>

            @if(isset($client['telefono']) && $client['telefono'])
                <a href="tel:{{ $client['telefono'] }}" class="urgent-btn" style="color: white !important;">
                    Llamar Cliente
                </a>
            @endif

            @if(isset($client['telefono']) && $client['telefono'])
                <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $client['telefono']) }}"
                   target="_blank" class="contact-btn" style="color: white !important; background-color: #25d366;">
                    WhatsApp
                </a>
            @endif

            @if(isset($client['email']) && $client['email'])
                <a href="mailto:{{ $client['email'] }}" class="contact-btn" style="color: white !important;">
                    Enviar Email
                </a>
            @endif
        </div>

        <!-- Información del sistema -->
        <div style="background-color: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #6c757d;">
            <h4 style="margin-top: 0; color: #495057;">Información del Sistema</h4>
            <p style="margin: 5px 0; color: #6c757d; font-size: 14px;">
                <strong>Notificado a:</strong> {{ $staff['name'] }} ({{ $staff['role'] }})<br>
                <strong>Fecha de notificación:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}<br>
                <strong>Sistema:</strong> Panel Administrativo VASIR
            </p>
        </div>

        <div class="footer">
            <p><strong>Sistema de Gestión {{ $companyName }}</strong></p>
            <p>Esta es una notificación automática del sistema. Por favor, procede a revisar la reserva.</p>
            <hr style="margin: 15px 0;">
            <p><small>© {{ date('Y') }} VASIR. Panel de Administración - Sistema Interno.</small></p>
        </div>
    </div>
</body>
</html>
