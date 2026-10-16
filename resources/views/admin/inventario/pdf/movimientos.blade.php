<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Movimientos de inventario · {{ $sucursal->nombre }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #1f2430;
            line-height: 1.4;
        }

        /* ======= Hoja encuadrada, centrada ======= */
        .hoja {
            width: 100%;
            border: 1px solid #d9dde3;
            border-radius: 8px;
            overflow: hidden;
        }

        /* ======= Cabecera ======= */
        .topbar {
            background: #1f2430;
            color: #fff;
            padding: 16px 18px;
        }

        .topbar table {
            width: 100%;
            border-collapse: collapse;
        }

        .topbar .marca {
            font-size: 12px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #e8c15c;
            font-weight: bold;
        }

        .topbar .sucursal {
            font-size: 13px;
            color: #cfd4dc;
        }

        .topbar h1 {
            font-size: 19px;
            margin-top: 2px;
            text-align: center;
        }

        .topbar .fecha-cabecera {
            text-align: right;
            font-size: 9px;
            color: #cfd4dc;
        }

        .topbar .fecha-cabecera strong {
            display: block;
            color: #fff;
            font-size: 11px;
        }

        .separador {
            height: 4px;
            background: #e8c15c;
        }

        /* ======= Ficha de datos ======= */
        .bloque {
            padding: 14px 16px 6px;
        }

        table.ficha {
            width: 100%;
            margin: 0 auto 6px;
            border-collapse: collapse;
        }

        table.ficha td {
            width: 25%;
            padding: 9px 11px;
            border: 1px solid #e3e6ea;
            background: #f7f8fa;
            vertical-align: top;
            text-align: center;
        }

        table.ficha td.destacado {
            background: #fdf6e3;
            border-color: #e8c15c;
        }

        .ficha .etiqueta {
            display: block;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #8a91a0;
            margin-bottom: 2px;
        }

        .ficha .valor {
            display: block;
            font-size: 12px;
            font-weight: bold;
            color: #1f2430;
        }

        .ficha .subvalor {
            display: block;
            font-size: 9px;
            color: #8a91a0;
            margin-top: 2px;
        }

        /* ======= Resumen por tipo ======= */
        table.totales {
            width: 74%;
            margin: 0 auto 12px;
            border-collapse: collapse;
        }

        table.totales td {
            width: 33%;
            padding: 8px 10px;
            border: 1px solid #e3e6ea;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5b626e;
        }

        table.totales .n-big {
            display: block;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0;
            margin-top: 2px;
        }

        table.totales .n-big.entrada {
            color: #1d7a55;
        }

        table.totales .n-big.salida {
            color: #c0392b;
        }

        table.totales .n-big.ajuste {
            color: #a8791c;
        }

        /* ======= Títulos de sección ======= */
        .seccion-titulo {
            font-size: 11px;
            font-weight: bold;
            color: #1f2430;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 8px auto 10px;
            width: 100%;
            border-bottom: 2px solid #e8c15c;
            padding-bottom: 5px;
        }

        /* ======= Tabla de movimientos ======= */
        table.detalle {
            width: 100%;
            margin: 0 auto 8px;
            border-collapse: collapse;
        }

        table.detalle thead th {
            background: #1f2430;
            color: #fff;
            padding: 7px 9px;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: center;
        }

        table.detalle thead th.num {
            text-align: right;
        }

        table.detalle thead th.insumo {
            text-align: left;
        }

        table.detalle tbody td {
            padding: 5px 7px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 8.5px;
            text-align: center;
            vertical-align: middle;
        }

        table.detalle tbody td.num {
            text-align: right;
        }

        table.detalle tbody td.insumo {
            text-align: left;
            font-weight: bold;
        }

        table.detalle tbody tr:nth-child(even) {
            background: #f7f8fa;
        }

        table.detalle tfoot td {
            padding: 8px 9px;
            background: #f0f1f4;
            font-weight: bold;
            border-top: 2px solid #1f2430;
            font-size: 9.5px;
            text-align: center;
        }

        table.detalle tfoot td.num {
            text-align: right;
        }

        table.detalle tfoot td.insumo {
            text-align: left;
        }

        /* ======= Etiquetas de tipo ======= */
        .pill {
            display: inline-block;
            min-width: 42px;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .entrada {
            background: #d4edda;
            color: #155724;
        }

        .salida {
            background: #f8d7da;
            color: #721c24;
        }

        .ajuste {
            background: #fff3cd;
            color: #856404;
        }

        .fecha-celda .dia {
            display: block;
            font-weight: bold;
            font-size: 10px;
        }

        .fecha-celda .hora {
            display: block;
            color: #8a91a0;
            font-size: 8px;
        }

        .unidad {
            color: #8a91a0;
            font-size: 8.5px;
        }

        .negativo {
            color: #c0392b;
        }

        .positivo {
            color: #1d7a55;
        }

        .observacion {
            color: #5b626e;
            font-size: 8.5px;
        }

        /* ======= Pie fijo ======= */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f7f8fa;
            border-top: 2px solid #e8c15c;
            padding: 7px 14px;
            font-size: 8.5px;
            color: #5b626e;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            vertical-align: middle;
        }

        .footer .izq {
            text-align: left;
        }

        .footer .cen {
            text-align: center;
        }

        .footer .der {
            text-align: right;
        }

        .footer strong {
            color: #1f2430;
        }

        @page {
            margin: 14mm 12mm 20mm 12mm;
        }
    </style>
</head>

<body>
    <div class="hoja">
        <div class="topbar">
            <table>
                <tr>
                    <td style="width:30%">
                        <div class="marca">{{ $configuracionSitio->get('SITIO_NOMBRE') }}</div>
                        <div class="sucursal">Sucursal {{ $sucursal->nombre }}</div>
                    </td>
                    <td style="width:40%">
                        <h1>Movimientos de inventario</h1>
                    </td>
                    <td style="width:30%">
                        <div class="fecha-cabecera">
                            <strong>{{ $generado->format('d/m/Y H:i') }}</strong>
                            Generación del reporte
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="separador"></div>

        <div class="bloque">
            <table class="ficha">
                <tr>
                    <td>
                        <span class="etiqueta">Sucursal</span>
                        <span class="valor">{{ $sucursal->nombre }}</span>
                        <span class="subvalor">Centro de operaciones</span>
                    </td>
                    <td class="destacado">
                        <span class="etiqueta">Período</span>
                        <span class="valor">{{ $desde->format('d/m/Y') }} → {{ $hasta->format('d/m/Y') }}</span>
                        <span class="subvalor">Desde {{ $desde->format('H:i') }} hrs</span>
                    </td>
                    <td>
                        <span class="etiqueta">Generado por</span>
                        <span class="valor">{{ $empleado->nombre.' '.$empleado->apellido }}</span>
                        <span class="subvalor">Rol: {{ $empleado->rol?->claverol }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Movimientos del período</span>
                        <span class="valor">{{ $movimientos->count() }}</span>
                        <span class="subvalor">Entradas, salidas y ajustes</span>
                    </td>
                </tr>
            </table>

            @php
                $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
            @endphp

            <table class="totales">
                <tr>
                    <td>
                        Entradas
                        <span class="n-big entrada">{{ $fmt($totales['ENTRADA']) }}</span>
                        <span class="subvalor">{{ $conteos['ENTRADA'] }} movimiento(s)</span>
                    </td>
                    <td>
                        Salidas
                        <span class="n-big salida">{{ $fmt($totales['SALIDA']) }}</span>
                        <span class="subvalor">{{ $conteos['SALIDA'] }} movimiento(s)</span>
                    </td>
                    <td>
                        Ajustes
                        <span class="n-big ajuste">{{ $fmt($totales['AJUSTE']) }}</span>
                        <span class="subvalor">{{ $conteos['AJUSTE'] }} movimiento(s)</span>
                    </td>
                </tr>
            </table>

            <div class="seccion-titulo">Detalle de movimientos por fecha</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th style="width:11%">Fecha</th>
                        <th style="width:9%">Tipo</th>
                        <th style="width:24%" class="insumo">Insumo</th>
                        <th style="width:8%" class="num">Cant.</th>
                        <th style="width:14%" class="num">Ant. → Nuevo</th>
                        <th style="width:15%">Empleado</th>
                        <th style="width:19%">Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $movimiento)
                        @php
                            $clase = match ($movimiento->tipomovimiento) {
                                'ENTRADA' => 'entrada',
                                'SALIDA' => 'salida',
                                default => 'ajuste',
                            };
                        @endphp
                        <tr>
                            <td class="fecha-celda">
                                <span class="dia">{{ $movimiento->fechamovimiento->format('d/m/Y') }}</span>
                                <span class="hora">{{ $movimiento->fechamovimiento->format('H:i') }} hrs</span>
                            </td>
                            <td><span class="pill {{ $clase }}">{{ $movimiento->tipomovimiento }}</span></td>
                            <td class="insumo">
                                {{ $movimiento->inventarioSucursal?->insumo?->nombre }}
                                <span class="unidad">({{ $movimiento->inventarioSucursal?->insumo?->unidadMedida?->abreviatura ?? '' }})</span>
                            </td>
                            <td class="num"><b>{{ $fmt($movimiento->cantidad) }}</b></td>
                            <td class="num">
                                <span class="positivo">{{ $fmt($movimiento->cantidadanterior) }}</span> →
                                <b>{{ $fmt($movimiento->cantidadnueva) }}</b>
                            </td>
                            <td>
                                {{ $movimiento->empleado ? $movimiento->empleado->nombre.' '.$movimiento->empleado->apellido : '—' }}
                                @if ($movimiento->empleado?->rol)
                                    <span class="unidad" style="display:block;">{{ $movimiento->empleado->rol->claverol }}</span>
                                @endif
                            </td>
                            <td class="observacion">{{ $movimiento->observacion ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">No hay movimientos registrados para el período indicado.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Totales</td>
                        <td></td>
                        <td></td>
                        <td class="num">{{ $fmt($movimientos->sum('cantidad')) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="izq" style="width:33%">
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Inventario
                </td>
                <td class="cen" style="width:34%">
                    Generado el <strong>{{ $generado->format('d/m/Y') }}</strong> a las
                    <strong>{{ $generado->format('H:i') }}</strong>
                </td>
                <td class="der" style="width:33%">
                    <strong>Generado por:</strong> {{ $empleado->nombre.' '.$empleado->apellido }}
                    ({{ $empleado->rol?->claverol }})
                </td>
            </tr>
        </table>
    </div>
</body>

</html>