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
        .resumen-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }
        .resumen-card {
            flex: 1;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 12px;
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
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
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
        .font-bold {
            font-weight: bold;
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
        <div class="fecha">
            Generado el {{ $fecha_generacion }}
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

    <div class="resumen-container">
        <div class="resumen-card">
            <div class="resumen-numero">{{ $total_ventas }}</div>
            <div class="resumen-label">Total Compras</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-numero">{{ $ventas_completadas }}</div>
            <div class="resumen-label">Completadas</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-numero">{{ $ventas_pendientes }}</div>
            <div class="resumen-label">Pendientes</div>
        </div>
        <div class="resumen-card">
            <div class="resumen-numero">S/. {{ number_format($total_gastado, 2) }}</div>
            <div class="resumen-label">Total Gastado</div>
        </div>
    </div>

    @if(count($ventas) > 0)
        @foreach($ventas as $venta)
            <div class="venta-card">
                <div class="venta-header">
                    <div>
                        <span class="venta-fecha">Compra del {{ $venta['fecha'] }}</span>
                        <span class="estado estado-{{ strtolower(str_replace(' ', '-', $venta['estado'])) }}">
                            {{ $venta['estado'] }}
                        </span>
                    </div>
                    <div class="venta-total">Total: S/. {{ number_format($venta['total'], 2) }}</div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40%">Producto</th>
                            <th style="width: 15%" class="text-right">Cantidad</th>
                            <th style="width: 20%" class="text-right">Precio Unit.</th>
                            <th style="width: 25%" class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venta['productos'] as $producto)
                            <tr>
                                <td>{{ $producto['nombre'] }}</td>
                                <td class="text-right">{{ $producto['cantidad'] }}</td>
                                <td class="text-right">S/. {{ number_format($producto['precio'], 2) }}</td>
                                <td class="text-right font-bold">S/. {{ number_format($producto['subtotal'], 2) }}</td>
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
