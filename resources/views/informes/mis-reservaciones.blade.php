<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mis Reservaciones</title>
    <style>
        body {
            font-family: 'Georgia', 'Times New Roman', Times, serif;
            background: #fff;
            color: #232323;
            margin: 0;
            padding: 0 0 60px 0;
            font-size: 12px;
        }
        .main-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px 10px 24px;
            margin-bottom: 18px;
        }
        .logo-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .logo-img {
            width: 90px;
            margin-bottom: 0;
        }
        .business-details {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .business-info {
            color: #444;
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .fecha {
            text-align: right;
            color: #7c1d1d;
            font-size: 0.92rem;
            font-style: italic;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #7c1d1d;
            margin-bottom: 8px;
        }
        .subtitle {
            font-size: 1rem;
            color: #666;
            margin-bottom: 20px;
        }
        .cliente-info {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #7c1d1d;
            margin-bottom: 20px;
        }
        .cliente-info h3 {
            margin: 0 0 8px 0;
            color: #7c1d1d;
            font-size: 1.1rem;
        }
        .cliente-info p {
            margin: 4px 0;
            color: #444;
        }
        .resumen-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 6px;
            flex-wrap: wrap;
        }
        .resumen-card {
            flex: 1;
            min-width: 13%;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .resumen-numero {
            font-size: 1.5rem;
            font-weight: bold;
            color: #7c1d1d;
            margin-bottom: 4px;
        }
        .resumen-label {
            font-size: 0.85rem;
            color: #666;
            text-transform: uppercase;
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        .table th {
            background: #7c1d1d;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #5a1515;
        }
        .table td {
            padding: 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .estado {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-confirmada { background: #d4edda; color: #155724; }
        .estado-en-progreso { background: #cce7ff; color: #004085; }
        .estado-en_curso { background: #007bff; color: #ffffff; }
        .estado-reprogramada { background: #6f42c1; color: #ffffff; }
        .estado-finalizada { background: #d1ecf1; color: #0c5460; }
        .estado-cancelada { background: #f8d7da; color: #721c24; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding: 10px 0;
            background: white;
            z-index: 1000;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="main-header">
        <div class="logo-header">
            <img src="{{ public_path('images/logo.png') }}" alt="Logo VASIR" class="logo-img">
            <div class="business-details">
                <div class="business-info">
                    Teléfono: {{ $empresa['telefono'] }}<br>
                    Email: {{ $empresa['email'] }}<br>
                    Dirección: {{ $empresa['direccion'] }}
                </div>
            </div>
        </div>
    </div>

    <div class="header">
        <h1 class="title">MIS RESERVACIONES</h1>
        <p class="subtitle">Informe detallado de reservaciones realizadas</p>
    </div>

    <div class="cliente-info">
        <h3>Información del Cliente</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 1; min-width: 200px;">
                <p><strong>Nombre:</strong> {{ $cliente['nombre'] }}</p>
                <p><strong>Email:</strong> {{ $cliente['email'] }}</p>
                <p><strong>Teléfono:</strong> {{ $cliente['telefono'] }}</p>
                <p><strong>Dirección:</strong> {{ $cliente['direccion'] }}</p>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <p><strong>{{ $cliente['tipo_documento'] }}:</strong> {{ $cliente['numero_identificacion'] }}</p>
                <p><strong>Fecha de Nacimiento:</strong> {{ $cliente['fecha_nacimiento'] }}</p>
                <p><strong>Género:</strong> {{ $cliente['genero'] }}</p>
            </div>
        </div>
    </div>

    <!-- Tabla de Resumen de Estados -->
    <div style="margin-bottom: 25px;">
        <table style="width: 100%; border-collapse: collapse; border: 2px solid #7c1d1d; border-radius: 8px; overflow: hidden;">
            <thead>
                <tr style="background: #7c1d1d;">
                    <th colspan="7" style="color: white; padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold;">
                        RESUMEN DE RESERVAS POR ESTADO
                    </th>
                </tr>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">Total</th>
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">Pendientes</th>
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">Confirmadas</th>
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">En Curso</th>
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">Reprogramadas</th>
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">Finalizadas</th>
                    <th style="padding: 10px; text-align: center; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd;">Total Gastos</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 12px; text-align: center; font-size: 1.2rem; font-weight: bold; color: #7c1d1d; border: 1px solid #ddd; background: #f8f9fa;">
                        {{ $total_reservas }}
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold; color: #ffc107; border: 1px solid #ddd;">
                        {{ $reservas_pendientes }}
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold; color: #28a745; border: 1px solid #ddd;">
                        {{ $reservas_confirmadas }}
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold; color: #007bff; border: 1px solid #ddd;">
                        {{ $reservas_en_curso ?? 0 }}
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold; color: #6f42c1; border: 1px solid #ddd;">
                        {{ $reservas_reprogramadas ?? 0 }}
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold; color: #6c757d; border: 1px solid #ddd;">
                        {{ $reservas_completadas }}
                    </td>
                    <td style="padding: 12px; text-align: center; font-size: 1.2rem; font-weight: bold; color: #dc3545; border: 1px solid #ddd; background: #fff3cd;">
                        ${{ number_format($total_gastos ?? 0, 2) }}
                        <br><small style="font-size: 0.7rem; color: #856404; font-weight: normal;">(Solo reservas finalizadas)</small>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if(count($reservas) > 0)
        <table class="table">
            <thead>
                <tr style="background: #7c1d1d;">
                    <th colspan="7" style="color: white; padding: 12px; text-align: center; font-size: 1.1rem; font-weight: bold; border: none;">
                        DETALLE DE RESERVACIONES
                    </th>
                </tr>
                <tr>
                    <th style="width: 20%">Fecha</th>
                    <th style="width: 25%">Tours</th>
                    <th style="width: 10%">Menores</th>
                    <th style="width: 10%">Mayores</th>
                    <th style="width: 10%">Total Cupos</th>
                    <th style="width: 15%">Estado</th>
                    <th style="width: 10%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reservas as $reserva)
                    <tr>
                        <td>{{ $reserva['fecha'] }}</td>
                        <td>{{ $reserva['tours'] }}</td>
                        <td style="text-align: center;">{{ $reserva['menores_edad'] }}</td>
                        <td style="text-align: center;">{{ $reserva['mayores_edad'] }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ $reserva['total_cupos'] }}</td>
                        <td>
                            <span class="estado estado-{{ strtolower(str_replace(' ', '-', $reserva['estado'])) }}">
                                {{ $reserva['estado'] }}
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: bold;">${{ number_format($reserva['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>


    @else
        <div class="no-data">
            <p>No se encontraron reservaciones registradas.</p>
        </div>
    @endif

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema de AGENCIA VASIR</p>
        <p>Fecha de generación: {{ $fecha_generacion }}</p>
    </div>
</body>
</html>
