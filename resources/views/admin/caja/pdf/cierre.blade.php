<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Cierre de caja · {{ $apertura->caja?->nombre }}</title>
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

        /* ======= Resumen de cierre ======= */
        table.totales {
            width: 100%;
            margin: 0 auto 12px;
            border-collapse: collapse;
        }

        table.totales td {
            padding: 8px 10px;
            border: 1px solid #e3e6ea;
            text-align: center;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #5b626e;
        }

        table.totales .n-big {
            display: block;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0;
            margin-top: 2px;
        }

        table.totales .n-big.esperado {
            color: #1f2430;
        }

        table.totales .n-big.contado {
            color: #1d7a55;
        }

        table.totales .n-big.diferencia-cero {
            color: #1d7a55;
        }

        table.totales .n-big.diferencia-negativa {
            color: #c0392b;
        }

        table.totales .n-big.diferencia-positiva {
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

        /* ======= Tablas de detalle ======= */
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

        table.detalle thead th.izq {
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

        table.detalle tbody td.izq {
            text-align: left;
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

        table.detalle tfoot td.izq {
            text-align: left;
        }

        /* ======= Ventas de la jornada (solo esa sección) ======= */
        table.detalle-ventas thead th {
            border-bottom: 3px solid #e8c15c;
        }

        table.detalle-ventas tbody td {
            vertical-align: top;
            padding: 6px 7px;
        }

        table.detalle-ventas tbody td + td {
            border-left: 1px solid #edeff2;
        }

        table.detalle-ventas tbody td.num {
            background: #fdf9ee;
        }

        table.detalle-ventas tbody td.num strong {
            font-size: 10px;
            color: #1f2430;
        }

        table.detalle-ventas tfoot td {
            background: #fdf6e3;
            border-top: 2px solid #e8c15c;
            color: #1f2430;
        }

        table.detalle-ventas tbody tr {
            page-break-inside: avoid;
        }

        /* ======= Etiquetas ======= */
        .pill {
            display: inline-block;
            min-width: 42px;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .ingreso {
            background: #d4edda;
            color: #155724;
        }

        .egreso {
            background: #f8d7da;
            color: #721c24;
        }

        .detalle-linea {
            display: inline-block;
            background: #ffffff;
            border: 1px solid #e3e6ea;
            border-left: 3px solid #e8c15c;
            border-radius: 4px;
            padding: 1px 5px;
            margin: 1px 2px 2px 0;
            font-size: 7.5px;
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

        .vacio {
            text-align: center;
            color: #9aa1a9;
            padding: 14px;
        }

        .fecha-celda .dia {
            display: block;
            font-weight: bold;
            font-size: 9.5px;
        }

        .fecha-celda .hora {
            display: block;
            color: #8a91a0;
            font-size: 8px;
        }

        /* ======= Observación del cierre ======= */
        .observacion {
            margin: 4px 0 10px;
            padding: 8px 10px;
            border: 1px dashed #e8c15c;
            background: #fdf6e3;
            border-radius: 6px;
            font-size: 8.5px;
            color: #5b626e;
        }

        .observacion strong {
            color: #1f2430;
        }

        /* ======= Firmas ======= */
        .firmas {
            margin-top: 20px;
            table-layout: fixed;
            width: 100%;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            padding: 0 14px;
        }

        .firmas .linea {
            border-top: 1px solid #5b626e;
            margin-bottom: 4px;
            padding-top: 5px;
            font-weight: bold;
            font-size: 9px;
        }

        .firmas .rol {
            color: #8a91a0;
            font-size: 8px;
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
    @php
        $fmt = fn ($monto) => 'Bs. '.number_format((float) $monto, 2, ',', '.');
        $duracion = $apertura->fechaapertura->diff($cierre->fechacierre);
        $duracionTexto = ($duracion->d > 0 ? $duracion->d.' día(s) ' : '').$duracion->format('%h:%i');
        $diferencia = (float) $cierre->diferencia;
        $claseDiferencia = $diferencia < 0 ? 'diferencia-negativa' : ($diferencia > 0 ? 'diferencia-positiva' : 'diferencia-cero');
        $abrio = $apertura->empleado;
        $cerro = $cierre->empleado;
    @endphp

    <div class="hoja">
        <div class="topbar">
            <table>
                <tr>
                    <td style="width:30%">
                        <div class="marca">{{ $configuracionSitio->get('SITIO_NOMBRE') }}</div>
                        <div class="sucursal">Sucursal {{ $sucursal->nombre }}</div>
                    </td>
                    <td style="width:40%">
                        <h1>Reporte de cierre de caja</h1>
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
                        <span class="etiqueta">Caja</span>
                        <span class="valor">{{ $apertura->caja?->nombre }}</span>
                        <span class="subvalor">{{ $sucursal->nombre }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Abierta por</span>
                        <span class="valor">{{ $abrio ? $abrio->nombre.' '.$abrio->apellido : '—' }}</span>
                        <span class="subvalor">{{ $abrio?->rol?->claverol ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Apertura</span>
                        <span class="valor">{{ $apertura->fechaapertura->format('d/m/Y') }}</span>
                        <span class="subvalor">{{ $apertura->fechaapertura->format('H:i') }} hrs</span>
                    </td>
                    <td>
                        <span class="etiqueta">Fondo inicial</span>
                        <span class="valor">{{ $fmt($apertura->montoinicial) }}</span>
                        <span class="subvalor">Efectivo con el que se abrió</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="etiqueta">Estado</span>
                        <span class="valor">CERRADA</span>
                        <span class="subvalor">Jornada finalizada</span>
                    </td>
                    <td>
                        <span class="etiqueta">Cerrada por</span>
                        <span class="valor">{{ $cerro ? $cerro->nombre.' '.$cerro->apellido : '—' }}</span>
                        <span class="subvalor">{{ $cerro?->rol?->claverol ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Cierre</span>
                        <span class="valor">{{ $cierre->fechacierre->format('d/m/Y') }}</span>
                        <span class="subvalor">{{ $cierre->fechacierre->format('H:i') }} hrs · Duración {{ $duracionTexto }}</span>
                    </td>
                    <td class="destacado">
                        <span class="etiqueta">Diferencia</span>
                        <span class="valor">{{ ($diferencia < 0 ? '-' : ($diferencia > 0 ? '+' : '')).number_format(abs($diferencia), 2, ',', '.') }}</span>
                        <span class="subvalor">{{ $diferencia == 0 ? 'Caja cuadrada' : ($diferencia < 0 ? 'Faltante de efectivo' : 'Sobrante de efectivo') }}</span>
                    </td>
                </tr>
            </table>

            <table class="totales">
                <tr>
                    <td>
                        Ventas en efectivo
                        <span class="n-big">{{ $fmt($cierre->totalventasefectivo) }}</span>
                    </td>
                    <td>
                        Ventas en QR
                        <span class="n-big">{{ $fmt($cierre->totalventasqr) }}</span>
                    </td>
                    <td>
                        Ventas con tarjeta
                        <span class="n-big">{{ $fmt($cierre->totalventastarjeta) }}</span>
                    </td>
                    <td>
                        Total de ventas
                        <span class="n-big">{{ $fmt($cierre->totalventas) }}</span>
                        <span class="subvalor">{{ $ventas->count() }} venta(s) · {{ $pagos->count() }} pago(s)</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        Ingresos
                        <span class="n-big">{{ $fmt($cierre->totalingresos) }}</span>
                        <span class="subvalor">{{ $movimientos->where('tipomovimiento', 'INGRESO')->count() }} movimiento(s)</span>
                    </td>
                    <td>
                        Egresos
                        <span class="n-big">{{ $fmt($cierre->totalegresos) }}</span>
                        <span class="subvalor">{{ $movimientos->where('tipomovimiento', 'EGRESO')->count() }} movimiento(s)</span>
                    </td>
                    <td>
                        Efectivo esperado
                        <span class="n-big esperado">{{ $fmt($cierre->efectivoesperado) }}</span>
                        <span class="subvalor">Fondo + ventas en efectivo + ingresos - egresos</span>
                    </td>
                    <td class="destacado">
                        Efectivo contado
                        <span class="n-big contado">{{ $fmt($cierre->efectivocontado) }}</span>
                        <span class="subvalor">Diferencia: {{ ($diferencia < 0 ? '-' : ($diferencia > 0 ? '+' : '')).number_format(abs($diferencia), 2, ',', '.') }}</span>
                    </td>
                </tr>
            </table>

            @if ($cierre->observacion)
                <div class="observacion">
                    <strong>Observación del cierre:</strong> {{ $cierre->observacion }}
                </div>
            @endif

        

            <div class="seccion-titulo">Ventas de la jornada y su detalle</div>
            <table class="detalle detalle-ventas">
                <thead>
                    <tr>
                        <th style="width:12%">Fecha</th>
                        <th style="width:11%">Pedido</th>
                        <th class="izq" style="width:45%">Qué se vendió</th>
                        <th style="width:14%">Método Pago</th>
                        <th class="num" style="width:18%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventas as $venta)
                        @php
                            $pagosDeLaVenta = $pagos->where('idventa', $venta->id);
                        @endphp
                        <tr>
                            <td class="fecha-celda">
                                <span class="dia">{{ $venta->fechaventa->format('d/m/Y') }}</span>
                                <span class="hora">{{ $venta->fechaventa->format('H:i') }} hrs</span>
                            </td>
                            <td>
                                #{{ $venta->pedido?->nropedido }}
                                <br>
                                <span class="unidad">
                                    {{ $venta->pedido?->mesa
                                        ? 'Mesa '.$venta->pedido->mesa->numeromesa
                                        : str_replace('_', ' ', $venta->pedido?->tipopedido) }}
                                </span>
                            </td>
                            <td class="izq">
                                @forelse ($venta->pedido?->detalles ?? [] as $detalle)
                                    <span class="detalle-linea">
                                        {{ $detalle->idpromocionsucursal !== null
                                            ? $detalle->promocionSucursal?->promocion?->nombre
                                            : $detalle->sucursalProducto?->producto?->nombre }}
                                        ×{{ $detalle->cantidad }}
                                    </span>
                                @empty
                                    <span class="unidad">Sin líneas</span>
                                @endforelse
                            </td>
                            <td>
                                @forelse ($pagosDeLaVenta as $pago)
                                    {{ $pago->metodoPago?->nombre }}{{ $loop->last ? '' : ', ' }}
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="num"><strong>{{ $fmt($venta->totalventa) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="vacio">Esta jornada no registró ventas cobradas.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Totales</td>
                        <td>{{ $ventas->count() }} venta(s)</td>
                        <td class="izq"></td>
                        <td>{{ $pagos->count() }} pago(s)</td>
                        <td class="num">{{ $fmt($ventas->sum('totalventa')) }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="seccion-titulo">Movimientos de caja de la jornada</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th style="width:15%">Fecha</th>
                        <th style="width:12%">Tipo</th>
                        <th class="izq" style="width:30%">Concepto</th>
                        <th style="width:20%">Empleado</th>
                        <th class="num" style="width:23%">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $movimiento)
                        <tr>
                            <td class="fecha-celda">
                                <span class="dia">{{ $movimiento->fechamovimiento->format('d/m/Y') }}</span>
                                <span class="hora">{{ $movimiento->fechamovimiento->format('H:i') }} hrs</span>
                            </td>
                            <td>
                                <span class="pill {{ $movimiento->tipomovimiento === 'INGRESO' ? 'ingreso' : 'egreso' }}">
                                    {{ $movimiento->tipomovimiento }}
                                </span>
                            </td>
                            <td class="izq">
                                <strong>{{ $movimiento->concepto }}</strong>
                                @if ($movimiento->observacion)
                                    <br><span class="unidad">{{ $movimiento->observacion }}</span>
                                @endif
                            </td>
                            <td>{{ $movimiento->empleado?->nombre }} {{ $movimiento->empleado?->apellido }}</td>
                            <td class="num {{ $movimiento->tipomovimiento === 'INGRESO' ? 'positivo' : 'negativo' }}">
                                <strong>{{ $movimiento->tipomovimiento === 'INGRESO' ? '+' : '-' }}{{ $fmt($movimiento->monto) }}</strong>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="vacio">Esta jornada no registró movimientos de caja.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Totales</td>
                        <td>{{ $movimientos->count() }} movimiento(s)</td>
                        <td class="izq"></td>
                        <td></td>
                        <td class="num">
                            <span class="positivo">+{{ $fmt($totalesMovimientos['INGRESO']) }}</span>
                            ·
                            <span class="negativo">-{{ $fmt($totalesMovimientos['EGRESO']) }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>

            <table class="firmas">
                <tr>
                    <td>
                        <div class="linea">{{ $abrio ? $abrio->nombre.' '.$abrio->apellido : '—' }}</div>
                        <div class="rol">Quien abrió la caja · {{ $abrio?->rol?->claverol ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="linea">{{ $cerro ? $cerro->nombre.' '.$cerro->apellido : '—' }}</div>
                        <div class="rol">Quien cerró la caja · {{ $cerro?->rol?->claverol ?? '—' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="izq" style="width:33%">
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Caja
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
