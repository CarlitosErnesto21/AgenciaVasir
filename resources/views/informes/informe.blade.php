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
            margin-bottom: 18px;
        }
        .titulo {
            font-size: 1.1rem;
            font-weight: bold;
            color: #7c1d1d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .descripcion {
            color: #444;
            font-size: 0.85rem;
            margin-bottom: 14px;
            font-style: italic;
        }
        /* Tablas profesionales y compactas */
        .tabla-informe {
            width: 92%;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 4px;
            font-size: 0.78rem;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(60, 0, 0, 0.04);
            margin-bottom: 10px;
            border: 1px solid #e5e7eb;
        }
        .tabla-informe thead tr {
            background: #f7fafc;
        }
        .th-informe {
            background: #495057;
            color: #fff;
            border-bottom: 2px solid #495057;
            padding: 8px 6px;
            font-weight: 700;
            text-align: center;
            font-size: 0.7rem;
            letter-spacing: 0.1px;
        }
        .tabla-informe tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .tabla-informe tbody tr:nth-child(odd) {
            background: #f4f6f8;
        }
        .td-informe {
            border-bottom: 1px solid #e5e7eb;
            padding: 4px 2px;
            text-align: center;
            color: #232323;
            font-size: 0.80rem;
        }
        .td-informe-total {
            border-top: 2px solid #495057;
            font-weight: bold;
            background: #f8f9fa;
            color: #495057;
            text-align: center;
            padding: 8px;
            font-size: 0.85rem;
        }
        tfoot td {
            font-size: 0.85rem;
        }
        /* Bordes redondeados para la tabla */
        .tabla-informe th:first-child {
            border-top-left-radius: 8px;
        }
        .tabla-informe th:last-child {
            border-top-right-radius: 8px;
        }
        .tabla-informe tr:last-child td:first-child {
            border-bottom-left-radius: 8px;
        }
        .tabla-informe tr:last-child td:last-child {
            border-bottom-right-radius: 8px;
        }
        /* Espaciado entre tablas */
        .tabla-separador {
            margin-bottom: 16px;
        }
        /* Título de mes */
        .mes-titulo {
            font-size:0.85rem;
            font-weight:bold;
            color:#444;
            margin-bottom:4px;
            margin-top:8px;
            letter-spacing: 0.5px;
        }
        /* Tabla total general */
        .tabla-total-general {
            margin-top: 18px;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        /* Resumen mensual (3 columnas horizontales) - Estilo reservas-cliente */
        .resumen-mes {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            padding: 0 20px;
        }
        .resumen-mes-header {
            font-weight: 700;
            color: #495057;
            margin-bottom: 10px;
            font-size: 1.1rem;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            grid-column: 1 / -1;
        }
        .resumen-mes-stats {
            display: contents;
        }
        .resumen-stat {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        .resumen-stat.tours { border-left: 4px solid #007bff; }
        .resumen-stat.cupos { border-left: 4px solid #28a745; }
        .resumen-stat.ingresos { border-left: 4px solid #ffc107; }
        .resumen-stat-numero {
            font-size: 1.6rem;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .resumen-stat-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tarjeta de total general más limpia */
        .total-card {
            width: 92%;
            max-width: 520px;
            margin: 24px auto 40px auto;
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e6eef9;
            box-shadow: 0 4px 10px rgba(14,31,66,0.06);
            overflow: hidden;
        }
        .total-card .accent {
            height: 6px;
            background: linear-gradient(90deg, #1e3a8a 0%, #3b82f6 100%);
        }
        .total-card .content {
            padding: 18px 22px;
            text-align: center;
        }
        .total-card .label { color: #334155; font-weight: 600; margin-bottom: 8px; }
        .total-card .amount { color: #059669; font-size: 1.25rem; font-weight: 800; }
    </style>
</head>
<body>
    <!-- Business Header -->
    <div class="main-header">
        <div class="logo-header">
            <img src="{{ public_path('images/logo.png') }}" alt="Logo VASIR" class="logo-img">
        </div>
        <div class="fecha">
            Fecha de emisión: {{ $fecha_emision }}
        </div>
    </div>
    <!-- Usuario que descarga -->
    <div style="text-align: center; margin-bottom: 15px; padding: 8px 20px; background: #f8f9fa; border-left: 4px solid #7c1d1d; font-size: 0.8rem; color: #666;">
        <strong>Descargado por:</strong> {{ $usuario_descarga['nombre'] ?? 'Usuario no identificado' }} ({{ $usuario_descarga['email'] ?? 'Email no disponible' }})
    </div>
    <!-- Report Title & Description -->
    <div class="header">
        <div class="titulo">{{ $titulo }}</div>
        <div class="descripcion">
            Informe mensual de cupos vendidos de tours finalizados, segmentados por menores y mayores de edad.
        </div>
    </div>
    <!-- Data Tables -->
    @php
        $totalGeneral = 0;
    @endphp
    @foreach($mesesData as $mesData)
        <div class="tabla-separador">
            <div class="mes-titulo">
                Mes: {{ \Carbon\Carbon::createFromFormat('Y-m', $mesData['mes'])->translatedFormat('F Y') }}
            </div>
            @php
                // Totales generales del mes para el resumen
                $totalTours = count($mesData['tours']);
                $totalCupos = array_sum(array_column($mesData['tours'], 'cupos_vendidos'));
                $totalMes = array_sum(array_column($mesData['tours'], 'subtotal'));
            @endphp

            <div class="resumen-mes">
                <div class="resumen-mes-header">Resumen del Mes</div>
                <div class="resumen-mes-stats">
                    <div class="resumen-stat tours">
                        <div class="resumen-stat-numero" style="color: #007bff;">{{ $totalTours }}</div>
                        <div class="resumen-stat-label">TOURS</div>
                    </div>
                    <div class="resumen-stat cupos">
                        <div class="resumen-stat-numero" style="color: #28a745;">{{ $totalCupos }}</div>
                        <div class="resumen-stat-label">CUPOS VENDIDOS</div>
                    </div>
                    <div class="resumen-stat ingresos">
                        <div class="resumen-stat-numero" style="color: #ffc107;">${{ number_format($totalMes, 2) }}</div>
                        <div class="resumen-stat-label">INGRESOS</div>
                    </div>
                </div>
            </div>

            <table class="tabla-informe">
                <thead>
                    <tr>
                        <th class="th-informe">Fecha Salida</th>
                        <th class="th-informe">Tour</th>
                        <th class="th-informe">Menores de Edad</th>
                        <th class="th-informe">Mayores de Edad</th>
                        <th class="th-informe">Cupos Vendidos</th>
                        <th class="th-informe">Precio Unitario</th>
                        <th class="th-informe">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalMes = 0; @endphp
                    @foreach($mesData['tours'] as $tour)
                    @php
                        $totalMes += $tour['subtotal'];
                    @endphp
                    <tr>
                        <td class="td-informe">{{ $tour['fecha'] }}</td>
                        <td class="td-informe">{{ $tour['nombre'] }}</td>
                        <td class="td-informe">{{ $tour['menores'] }}</td>
                        <td class="td-informe">{{ $tour['mayores'] }}</td>
                        <td class="td-informe">{{ $tour['cupos_vendidos'] }}</td>
                        <td class="td-informe">${{ number_format($tour['precio'], 2) }}</td>
                        <td class="td-informe">${{ number_format($tour['subtotal'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="td-informe-total" colspan="6">Total</td>
                        <td class="td-informe-total">${{ number_format($totalMes, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @php $totalGeneral += $totalMes; @endphp
        </div>
    @endforeach

    {{-- Mostrar meses sin datos si los hay --}}
    @if(!empty($mesesSinDatos))
        <div class="tabla-separador">
            <div class="mes-titulo" style="color: #666; font-style: italic; text-align: center; margin-top: 20px; margin-bottom: 10px;">
                Los siguientes meses no tienen registros de tours o ventas:
            </div>
            <div style="text-align: center; color: #888; font-size: 0.8rem; margin-bottom: 15px;">
                {{ implode(', ', $mesesSinDatos) }}
            </div>
        </div>
    @endif

    {{-- Total general en tarjeta centrada --}}
    <div class="total-card">
        <div class="accent"></div>
        <div class="content">
            <div class="label">Total de todos los meses seleccionados</div>
            <div class="amount">${{ number_format($totalGeneral, 2) }}</div>
        </div>
    </div>
</body>
</html>
