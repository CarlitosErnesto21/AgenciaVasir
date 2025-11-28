<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mis Compras</title>
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
        .resumen-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #ddd;
        }
        .resumen-table th {
            background: #7c1d1d;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        .resumen-table td {
            background: #f9f9f9;
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
            font-weight: bold;
            color: #7c1d1d;
            font-size: 14px;
        }
        .resumen-header {
            background: #7c1d1d;
            color: white;
            text-align: center;
            padding: 8px;
            font-weight: bold;
            font-size: 12px;
        }
        .venta-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .venta-header {
            background: #7c1d1d;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .venta-fecha {
            font-size: 1rem;
        }
        .venta-total {
            font-size: 1.1rem;
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 10px;
        }
        .table th {
            background: #f8f9fa;
            color: #333;
            padding: 8px 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }
        .table td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .table tr:last-child td {
            border-bottom: none;
        }
        .estado {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .estado-pendiente { background: #fff3cd; color: #856404; }
        .estado-completada { background: #d4edda; color: #155724; }
        .estado-cancelada { background: #f8d7da; color: #721c24; }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            background: white;
            z-index: 1000;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .info-note {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 5px;
            padding: 8px 12px;
            margin-bottom: 15px;
            font-size: 10px;
            color: #0c5460;
            font-style: italic;
            text-align: center;
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
        <h1 class="title">MIS COMPRAS</h1>
        <p class="subtitle">Informe detallado de compras realizadas</p>
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

    <div class="resumen-header">RESUMEN DE COMPRAS</div>
    <table class="resumen-table">
        <thead>
            <tr>
                <th>TOTAL COMPRAS</th>
                <th>COMPLETADAS</th>
                <th>PENDIENTES</th>
                <th>TOTAL GASTADO</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $total_ventas }}</td>
                <td>{{ $ventas_completadas }}</td>
                <td>{{ $ventas_pendientes }}</td>
                <td>$ {{ number_format($total_gastado, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="info-note">
        El total gastado corresponde únicamente a compras con estado COMPLETADA
    </div>

    @if(count($ventas) > 0)
        <div class="resumen-header">DETALLE DE COMPRAS</div>
        @foreach($ventas as $venta)
            <div class="venta-card">
                <div class="venta-header">
                    <div>
                        <span class="venta-fecha">Compra del {{ $venta['fecha'] }}</span>
                        <span class="estado estado-{{ strtolower(str_replace(' ', '-', $venta['estado'])) }}">
                            {{ $venta['estado'] }}
                        </span>
                    </div>
                    <div class="venta-total">Total: $ {{ number_format($venta['total'], 2) }}</div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40%">Producto</th>
                            <th style="width: 15%; text-align: center;">Cantidad</th>
                            <th style="width: 20%; text-align: right;">Precio Unit.</th>
                            <th style="width: 25%; text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venta['productos'] as $producto)
                            <tr>
                                <td>{{ $producto['nombre'] }}</td>
                                <td style="text-align: center;">{{ $producto['cantidad'] }}</td>
                                <td style="text-align: right;">$ {{ number_format($producto['precio'], 2) }}</td>
                                <td style="text-align: right; font-weight: bold;">$ {{ number_format($producto['subtotal'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">
            <p>No se encontraron compras registradas.</p>
        </div>
    @endif

    <div class="footer">
        <p>Este documento fue generado automáticamente por el sistema de AGENCIA VASIR</p>
        <p>Fecha de generación: {{ $fecha_generacion }}</p>
    </div>
</body>
</html>
