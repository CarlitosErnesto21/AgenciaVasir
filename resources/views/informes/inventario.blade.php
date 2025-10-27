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

        /* Estadísticas Generales */
        .estadisticas-generales {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 12px;
            padding: 0 20px;
        }
        .estadistica-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .estadistica-card.disponibles { border-left: 4px solid #28a745; }
        .estadistica-card.stock-bajo { border-left: 4px solid #ffc107; }
        .estadistica-card.agotados { border-left: 4px solid #dc3545; }
        .estadistica-numero {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .estadistica-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tablas */
        .tabla-seccion {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .seccion-titulo {
            font-size: 1.1rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 8px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            margin-left: 20px;
            margin-right: 20px;
        }
        .tabla-inventario {
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
        .tabla-inventario th {
            background: #495057;
            color: #fff;
            padding: 8px 6px;
            font-weight: 700;
            text-align: center;
            font-size: 0.7rem;
            letter-spacing: 0.1px;
        }
        .tabla-inventario tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .tabla-inventario tbody tr:nth-child(odd) {
            background: #ffffff;
        }
        .tabla-inventario td {
            border-bottom: 1px solid #dee2e6;
            padding: 6px 4px;
            text-align: center;
            font-size: 0.7rem;
        }

        /* Estados de productos */
        .estado-disponible { color: #28a745; font-weight: bold; }
        .estado-stock-bajo { color: #ffc107; font-weight: bold; }
        .estado-agotado { color: #dc3545; font-weight: bold; }

        /* Resumen por categoría */
        .resumen-categoria {
            background: #e9ecef;
            border-radius: 6px;
            padding: 8px;
            margin: 10px 20px;
        }
        .categoria-header {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .categoria-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            font-size: 0.7rem;
        }
        .categoria-stat {
            text-align: center;
            padding: 4px;
            background: #fff;
            border-radius: 4px;
        }

        /* Movimientos recientes */
        .movimiento-entrada { background-color: #d4edda !important; }
        .movimiento-salida { background-color: #f8d7da !important; }
        .movimiento-ajuste { background-color: #d1ecf1 !important; }

        /* Responsive adjustments */
        @media print {
            .estadisticas-generales {
                grid-template-columns: repeat(3, 1fr);
            }
            .tabla-inventario {
                font-size: 0.65rem;
            }
            .tabla-inventario th,
            .tabla-inventario td {
                padding: 4px 2px;
            }
        }

        .valor-total {
            text-align: right;
            font-weight: bold;
            color: #28a745;
        }

        .page-break {
            page-break-before: always;
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
            <div class="business-details">
                <div class="business-info">
                    Dirección: Chalatenango, El Salvador<br>
                    Teléfono: +503 7985 8777<br>
                    Correo: {{ config('mail.from.address', 'vasirtours19@gmail.com') }}
                </div>
            </div>
        </div>
        <div class="fecha">
            Fecha de emisión: {{ $fecha_emision }}
        </div>
    </div>

    <!-- Report Title & Description -->
    <div class="header">
        <div class="titulo">{{ $titulo }}</div>
        <div class="descripcion">
            Análisis completo del estado del inventario, stock disponible, productos críticos y movimientos recientes.
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="estadisticas-generales">
        <div class="estadistica-card disponibles">
            <div class="estadistica-numero" style="color: #28a745;">{{ $estadisticas['productos_disponibles'] }}</div>
            <div class="estadistica-label">Productos Disponibles</div>
        </div>
        <div class="estadistica-card stock-bajo">
            <div class="estadistica-numero" style="color: #ffc107;">{{ $estadisticas['productos_stock_bajo'] }}</div>
            <div class="estadistica-label">Stock Bajo</div>
        </div>
        <div class="estadistica-card agotados">
            <div class="estadistica-numero" style="color: #dc3545;">{{ $estadisticas['productos_agotados'] }}</div>
            <div class="estadistica-label">Agotados</div>
        </div>
    </div>

    <!-- Valor Total del Inventario -->
    <div class="resumen-categoria">
        <div class="categoria-header" style="text-align: center;">
            $ Valor Total del Inventario: ${{ number_format($estadisticas['valor_total_inventario'], 2) }}
        </div>
        <div style="text-align: center; font-size: 0.8rem; color: #666; margin-top: 5px;">
            Total de {{ $estadisticas['total_productos'] }} productos |
            {{ $estadisticas['productos_requieren_reabastecimiento'] }} requieren reabastecimiento
        </div>
    </div>

    <!-- Productos que Requieren Reabastecimiento (Críticos) -->
    @if(count($productos_agotados) > 0 || count($productos_stock_bajo) > 0)
        <div class="tabla-seccion">
            <div class="seccion-titulo" style="border-left-color: #dc3545;">
                • PRODUCTOS CRÍTICOS - REQUIEREN REABASTECIMIENTO URGENTE
            </div>
            <table class="tabla-inventario">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                        <th>Precio Unit.</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos_agotados as $producto)
                    <tr>
                        <td>{{ $producto['nombre'] }}</td>
                        <td>{{ $producto['categoria'] }}</td>
                        <td>{{ $producto['stock_actual'] }}</td>
                        <td>{{ $producto['stock_minimo'] }}</td>
                        <td class="estado-agotado">{{ $producto['estado'] }}</td>
                        <td>${{ number_format($producto['precio'], 2) }}</td>
                        <td class="valor-total">${{ number_format($producto['valor_total'], 2) }}</td>
                    </tr>
                    @endforeach
                    @foreach($productos_stock_bajo as $producto)
                    <tr>
                        <td>{{ $producto['nombre'] }}</td>
                        <td>{{ $producto['categoria'] }}</td>
                        <td>{{ $producto['stock_actual'] }}</td>
                        <td>{{ $producto['stock_minimo'] }}</td>
                        <td class="estado-stock-bajo">{{ $producto['estado'] }}</td>
                        <td>${{ number_format($producto['precio'], 2) }}</td>
                        <td class="valor-total">${{ number_format($producto['valor_total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Resumen por Categorías -->
    @if($resumen_por_categoria->count() > 0)
        <div class="tabla-seccion">
            <div class="seccion-titulo">• RESUMEN POR CATEGORÍAS</div>
            <table class="tabla-inventario">
                <thead>
                    <tr>
                        <th>CATEGORÍA</th>
                        <th>TOTAL PRODUCTOS</th>
                        <th>STOCK TOTAL</th>
                        <th>PRODUCTOS AGOTADOS</th>
                        <th>VALOR TOTAL (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resumen_por_categoria as $categoria)
                    <tr>
                        <td style="text-align: left; padding-left: 8px; font-weight: bold; color: #495057;">
                            {{ $categoria['categoria'] }}
                        </td>
                        <td style="font-weight: 600;">{{ $categoria['total_productos'] }}</td>
                        <td style="font-weight: 600;">{{ $categoria['stock_total'] }}</td>
                        <td style="font-weight: bold; color: #dc3545;">{{ $categoria['productos_agotados'] }}</td>
                        <td style="font-weight: bold; color: #28a745;">${{ number_format($categoria['valor_total'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-top: 2px solid #495057;">
                        <td style="font-weight: bold; color: #495057;">TOTALES</td>
                        <td style="font-weight: bold; color: #495057;">{{ $resumen_por_categoria->sum('total_productos') }}</td>
                        <td style="font-weight: bold; color: #495057;">{{ $resumen_por_categoria->sum('stock_total') }}</td>
                        <td style="font-weight: bold; color: #dc3545;">{{ $resumen_por_categoria->sum('productos_agotados') }}</td>
                        <td style="font-weight: bold; color: #28a745;">${{ number_format($resumen_por_categoria->sum('valor_total'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <!-- Productos Disponibles (Solo los primeros 20 para no sobrecargar) -->
    @if(count($productos_disponibles) > 0)
    <div class="tabla-seccion">
        <div class="seccion-titulo" style="border-left-color: #28a745;">
            • PRODUCTOS DISPONIBLES (Mostrando primeros 20)
        </div>
        <table class="tabla-inventario">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Precio Unit.</th>
                    <th>Valor Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($productos_disponibles, 0, 20) as $producto)
                <tr>
                    <td>{{ $producto['nombre'] }}</td>
                    <td>{{ $producto['categoria'] }}</td>
                    <td>{{ $producto['stock_actual'] }}</td>
                    <td>{{ $producto['stock_minimo'] }}</td>
                    <td>${{ number_format($producto['precio'], 2) }}</td>
                    <td class="valor-total">${{ number_format($producto['valor_total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(count($productos_disponibles) > 20)
        <div style="text-align: center; font-style: italic; color: #666; margin-top: 10px;">
            ... y {{ count($productos_disponibles) - 20 }} productos disponibles más
        </div>
        @endif
    </div>
    @endif

    <!-- Movimientos Recientes -->
    @if($movimientos_recientes->count() > 0)
        <div class="tabla-seccion">
            <div class="seccion-titulo">• MOVIMIENTOS RECIENTES (Últimos 30 días)</div>
            <table class="tabla-inventario">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Motivo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movimientos_recientes as $movimiento)
                    <tr class="movimiento-{{ strtolower($movimiento['tipo']) }}">
                        <td>{{ $movimiento['fecha'] }}</td>
                        <td>{{ $movimiento['producto'] }}</td>
                        <td>{{ $movimiento['tipo'] }}</td>
                        <td>{{ $movimiento['cantidad'] }}</td>
                        <td>{{ $movimiento['motivo'] }}</td>
                        <td>{{ $movimiento['usuario'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</body>
</html>
