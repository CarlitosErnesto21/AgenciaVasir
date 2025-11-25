<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body {
            font-family: 'Georgia', 'Times New Roman', Times, serif;
            background: #fff;
            color: #232323;
            margin: 0;
            padding: 0;
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
        .titulo {
            font-size: 1.3rem;
            font-weight: bold;
            color: #7c1d1d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .descripcion {
            color: #444;
            font-size: 0.9rem;
            margin-bottom: 14px;
            font-style: italic;
        }

        /* Información del cliente */
        .cliente-info {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        .cliente-info h3 {
            font-size: 1.1rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 5px;
        }
        .cliente-datos {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            font-size: 0.85rem;
        }
        .cliente-datos .dato {
            color: #495057;
        }
        .cliente-datos .dato strong {
            color: #212529;
        }

        /* Estadísticas Generales */
        .estadisticas-generales {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 0 20px;
        }
        .estadistica-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .estadistica-card.total { border-left: 4px solid #007bff; }
        .estadistica-card.monto { border-left: 4px solid #28a745; }
        .estadistica-card.completadas { border-left: 4px solid #28a745; }
        .estadistica-numero {
            font-size: 1.6rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .estadistica-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tablas */
        .tabla-ventas {
            width: 95%;
            margin: 0 auto;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.75rem;
            background: #fff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }
        .tabla-ventas th {
            background: #495057;
            color: #fff;
            padding: 8px 6px;
            font-weight: 700;
            text-align: center;
            font-size: 0.7rem;
            letter-spacing: 0.1px;
        }
        .tabla-ventas tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .tabla-ventas tbody tr:nth-child(odd) {
            background: #ffffff;
        }
        .tabla-ventas td {
            border-bottom: 1px solid #dee2e6;
            padding: 6px 4px;
            text-align: center;
            font-size: 0.7rem;
        }

        /* Tabla de detalles de productos */
        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 0.65rem;
        }
        .tabla-detalles th {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 4px;
            text-align: center;
        }
        .tabla-detalles td {
            border: 1px solid #dee2e6;
            padding: 4px;
            text-align: center;
        }

        /* Estados de ventas */
        .estado-completada { color: #28a745; font-weight: bold; }
        .estado-pendiente { color: #ffc107; font-weight: bold; }
        .estado-cancelada { color: #dc3545; font-weight: bold; }

        /* Totales */
        .tabla-totales {
            background: #f8f9fa;
            border: 2px solid #495057;
            border-radius: 6px;
            margin: 20px auto;
            width: 95%;
        }
        .tabla-totales th {
            background: #495057;
            color: #fff;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        .tabla-totales td {
            padding: 8px;
            text-align: center;
            font-weight: bold;
            color: #495057;
        }

        /* Responsive adjustments */
        @media print {
            .estadisticas-generales {
                grid-template-columns: repeat(3, 1fr);
            }
            .tabla-ventas {
                font-size: 0.65rem;
            }
            .tabla-ventas th,
            .tabla-ventas td {
                padding: 4px 2px;
            }
        }

        .page-break {
            page-break-before: always;
        }

        .valor-total {
            text-align: right;
            font-weight: bold;
            color: #28a745;
        }

        /* Sin datos */
        .sin-datos {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px auto;
        }

        .detalle-venta {
            margin-top: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <!-- Business Header -->
    <div class="main-header">
        <div class="logo-header">
            @php
                $logoPath = public_path('images/logo.png');
                $logoData = '';
                if (file_exists($logoPath)) {
                    $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                }
            @endphp
            @if($logoData)
                <img src="{{ $logoData }}" alt="Logo VASIR" class="logo-img">
            @else
                <div style="width: 90px; height: 60px; background: #991b1b; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.7rem;">VASIR</div>
            @endif
        </div>
        <div class="fecha">
            Fecha de emisión: {{ $fecha_emision }}
        </div>
    </div>
    <!-- Usuario que descarga -->
    <div style="text-align: center; margin-bottom: 15px; padding: 8px 20px; background: #f8f9fa; border-left: 4px solid #28a745; font-size: 0.8rem; color: #666;">
        <strong>Descargado por:</strong> {{ $usuario_descarga['nombre'] ?? 'Usuario no identificado' }} ({{ $usuario_descarga['email'] ?? 'Email no disponible' }})
    </div>

    <!-- Report Title & Description -->
    <div class="header">
        <div class="titulo">{{ $titulo }}</div>
        <div class="descripcion">
            Historial detallado de compras/ventas realizadas por el cliente, incluyendo productos adquiridos y montos.
        </div>
    </div>

    <!-- Información del Cliente -->
    <div class="cliente-info">
        <h3>Información del Cliente</h3>
        <div class="cliente-datos">
            <div class="dato"><strong>Nombre:</strong> {{ $cliente['name'] ?? 'N/A' }}</div>
            <div class="dato"><strong>Email:</strong> {{ $cliente['email'] ?? 'N/A' }}</div>
            <div class="dato"><strong>Documento:</strong> {{ $cliente['numero_identificacion'] ?? 'N/A' }}</div>
            <div class="dato"><strong>Teléfono:</strong> {{ $cliente['telefono'] ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="estadisticas-generales">
        <div class="estadistica-card total">
            <div class="estadistica-numero" style="color: #007bff;">{{ $estadisticas['total_ventas'] }}</div>
            <div class="estadistica-label">Total Ventas</div>
        </div>
        <div class="estadistica-card monto">
            <div class="estadistica-numero" style="color: #28a745;">${{ number_format($estadisticas['total_monto'], 2) }}</div>
            <div class="estadistica-label">Monto Total</div>
        </div>
        <div class="estadistica-card completadas">
            <div class="estadistica-numero" style="color: #28a745;">{{ $estadisticas['completadas'] }}</div>
            <div class="estadistica-label">Completadas</div>
        </div>
    </div>

    @if(count($ventas) > 0)
        <!-- Tabla de Ventas -->
        <table class="tabla-ventas">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Total</th>
                    <th>Productos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventas as $venta)
                <tr>
                    <td>#{{ $venta['id'] }}</td>
                    <td>{{ $venta['fecha'] }}</td>
                    <td class="estado-{{ strtolower($venta['estado']) }}">{{ $venta['estado_label'] }}</td>
                    <td class="valor-total">${{ number_format($venta['total'], 2) }}</td>
                    <td style="text-align: left; font-size: 0.65rem;">
                        @if(count($venta['productos']) > 0)
                            @foreach($venta['productos'] as $producto)
                                <div style="margin-bottom: 2px;">
                                    • {{ $producto['nombre'] }} (Cant: {{ $producto['cantidad'] }}, ${{ number_format($producto['precio'], 2) }})
                                </div>
                            @endforeach
                        @else
                            Sin productos
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Resumen por Estado -->
        <table class="tabla-totales">
            <thead>
                <tr>
                    <th colspan="4">Resumen por Estado de Ventas</th>
                </tr>
                <tr>
                    <th>Estado</th>
                    <th>Cantidad</th>
                    <th>Monto</th>
                    <th>Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($resumen_estados as $estado => $datos)
                <tr>
                    <td class="estado-{{ strtolower($estado) }}">{{ $datos['estado_label'] }}</td>
                    <td>{{ $datos['cantidad'] }}</td>
                    <td class="valor-total">${{ number_format($datos['monto'], 2) }}</td>
                    <td>{{ number_format($datos['porcentaje'], 1) }}%</td>
                </tr>
                @endforeach
                <tr style="background: #495057; color: #fff; font-weight: bold;">
                    <td style="color: #fff;">TOTAL</td>
                    <td style="color: #fff;">{{ $estadisticas['total_ventas'] }}</td>
                    <td style="color: #fff; text-align: center; font-weight: bold;">${{ number_format($estadisticas['total_monto'], 2) }}</td>
                    <td style="color: #fff;">100.0%</td>
                </tr>
            </tbody>
        </table>

        <!-- Productos más comprados -->
        @if(count($productos_populares) > 0)
        <table class="tabla-totales">
            <thead>
                <tr>
                    <th colspan="3">Productos Más Comprados</th>
                </tr>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad Total</th>
                    <th>Monto Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productos_populares as $producto)
                <tr>
                    <td style="text-align: left; padding-left: 10px;">{{ $producto['nombre'] }}</td>
                    <td>{{ $producto['cantidad_total'] }}</td>
                    <td class="valor-total">${{ number_format($producto['monto_total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    @else
        <div class="sin-datos">
            <h3>Sin Datos</h3>
            <p>Este cliente no tiene ventas registradas en el sistema.</p>
        </div>
    @endif
</body>
</html>
