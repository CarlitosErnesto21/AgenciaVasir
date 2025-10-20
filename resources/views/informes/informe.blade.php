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
            background: #991b1b;
            color: #fff;
            border-bottom: 2px solid #c53030;
            padding: 5px 2px;
            font-weight: 700;
            text-align: center;
            font-size: 0.82rem;
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
            border-top: 2px solid #991b1b;
            font-weight: bold;
            background: #fbe9e9;
            color: #991b1b;
            text-align: center;
            padding: 5px 2px;
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
    </style>
</head>
<body>
    <!-- Business Header -->
    <div class="main-header">
        <div class="logo-header">
            <img src="{{ asset('images/logo.png') }}" alt="Logo VASIR" class="logo-img">
            <div class="business-details">
                <div class="business-info">
                    Dirección: Chalatenango, El Salvador<br>
                    Teléfono: +503 7985 8777<br>
                    Correo: vasirtours19@gmail.com
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
            Informe mensual de cupos vendidos, segmentados por menores y mayores de edad, para cada tour disponible.
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
            <table class="tabla-informe">
                <thead>
                    <tr>
                        <th class="th-informe">Fecha</th>
                        <th class="th-informe">Nombre del Tour</th>
                        <th class="th-informe">Menores de Edad</th>
                        <th class="th-informe">Mayores de Edad</th>
                        <th class="th-informe">Cupos Vendidos</th>
                        <th class="th-informe">Precio</th>
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

    {{-- Tabla de total general --}}
    <div class="tabla-total-general">
        <table class="tabla-informe">
            <thead>
                <tr>
                    <th class="th-informe" colspan="6">Total de todos los meses seleccionados</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="td-informe-total" colspan="6">${{ number_format($totalGeneral, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
