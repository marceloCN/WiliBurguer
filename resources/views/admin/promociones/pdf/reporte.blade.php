<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Productos promocionados · {{ $sucursal->nombre }}</title>
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

        table.totales .n-big.promo {
            color: #a8791c;
        }

        table.totales .n-big.producto {
            color: #1d7a55;
        }

        table.totales .n-big.ahorro {
            color: #c0392b;
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

        /* ======= Bloque de cada promoción ======= */
        .promo {
            border: 1px solid #e3e6ea;
            border-radius: 6px;
            margin: 0 auto 10px;
            width: 100%;
            overflow: hidden;
        }

        .promo-cabecera {
            background: #f7f8fa;
            border-bottom: 1px solid #e3e6ea;
            padding: 7px 10px;
        }

        .promo-cabecera table {
            width: 100%;
            border-collapse: collapse;
        }

        .promo-cabecera td {
            vertical-align: middle;
            font-size: 9px;
        }

        .promo-nombre {
            font-size: 11px;
            font-weight: bold;
            color: #1f2430;
        }

        .promo-meta {
            color: #8a91a0;
            font-size: 8.5px;
            margin-top: 1px;
        }

        .promo-cabecera .der {
            text-align: right;
        }

        .pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .pill.descuento {
            background: #f8d7da;
            color: #721c24;
        }

        .pill.activa {
            background: #d4edda;
            color: #155724;
        }

        .pill.inactiva {
            background: #e2e3e5;
            color: #383d41;
        }

        /* ======= Tabla de productos de una promoción ======= */
        table.productos {
            width: 100%;
            border-collapse: collapse;
        }

        table.productos thead th {
            background: #1f2430;
            color: #fff;
            padding: 5px 8px;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        table.productos thead th.num {
            text-align: right;
        }

        table.productos thead th.prod {
            text-align: left;
        }

        table.productos tbody td {
            padding: 4px 8px;
            border-bottom: 1px solid #eef0f3;
            font-size: 8.5px;
            text-align: center;
        }

        table.productos tbody td.num {
            text-align: right;
        }

        table.productos tbody td.prod {
            text-align: left;
            font-weight: bold;
        }

        table.productos tbody tr:nth-child(even) {
            background: #fbfbfc;
        }

        .promo-pie {
            background: #fdf6e3;
            border-top: 1px solid #e8c15c;
            padding: 6px 10px;
            font-size: 8.5px;
            text-align: right;
        }

        .promo-pie strong {
            color: #1f2430;
        }

        .ahorro {
            color: #1d7a55;
            font-weight: bold;
        }

        .unidad {
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
    <div class="hoja">
        <div class="topbar">
            <table>
                <tr>
                    <td style="width:30%">
                        <div class="marca">{{ $configuracionSitio->get('SITIO_NOMBRE') }}</div>
                        <div class="sucursal">Sucursal {{ $sucursal->nombre }}</div>
                    </td>
                    <td style="width:40%">
                        <h1>Productos promocionados</h1>
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
                        <span class="subvalor">Vigencia que toca el rango</span>
                    </td>
                    <td>
                        <span class="etiqueta">Generado por</span>
                        <span class="valor">{{ $empleado->nombre.' '.$empleado->apellido }}</span>
                        <span class="subvalor">Rol: {{ $empleado->rol?->claverol }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Promociones en el período</span>
                        <span class="valor">{{ $totales['promociones'] }}</span>
                        <span class="subvalor">Vigentes o que tocan el rango</span>
                    </td>
                </tr>
            </table>

            <table class="totales">
                <tr>
                    <td>
                        Promociones
                        <span class="n-big promo">{{ $totales['promociones'] }}</span>
                        <span class="subvalor">En el período</span>
                    </td>
                    <td>
                        Productos promocionados
                        <span class="n-big producto">{{ $totales['productos'] }}</span>
                        <span class="subvalor">Participantes</span>
                    </td>
                    <td>
                        Ahorro total del período
                        <span class="n-big ahorro">Bs. {{ number_format($totales['ahorro'], 2) }}</span>
                        <span class="subvalor">Descuento acumulado de los combos</span>
                    </td>
                </tr>
            </table>

            <div class="seccion-titulo">Detalle de promociones y sus productos</div>

            @forelse ($promociones as $promocion)
                <div class="promo">
                    <div class="promo-cabecera">
                        <table>
                            <tr>
                                <td>
                                    <div class="promo-nombre">{{ $promocion->promocion?->nombre ?? '—' }}</div>
                                    <div class="promo-meta">
                                        Vigencia: {{ $promocion->fechainicio->format('d/m/Y') }} → {{ $promocion->fechafin->format('d/m/Y') }}
                                        · {{ $promocion->diasTexto() }}
                                    </div>
                                </td>
                                <td class="der">
                                    <span class="pill descuento">{{ $promocion->etiquetaDescuento() }}</span>
                                    <span class="pill {{ $promocion->activo ? 'activa' : 'inactiva' }}">
                                        {{ $promocion->activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <table class="productos">
                        <thead>
                            <tr>
                                <th style="width:40%" class="prod">Producto</th>
                                <th style="width:12%" class="num">Cant.</th>
                                <th style="width:20%" class="num">Precio unitario</th>
                                <th style="width:28%" class="num">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($promocion->productosSucursal as $producto)
                                <tr>
                                    <td class="prod">{{ $producto->producto?->nombre ?? '—' }}</td>
                                    <td class="num">{{ (int) $producto->pivot->cantidad_producto }}</td>
                                    <td class="num">Bs. {{ number_format((float) $producto->precioproducto, 2) }}</td>
                                    <td class="num">
                                        Bs. {{ number_format((float) $producto->precioproducto * (int) $producto->pivot->cantidad_producto, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">Sin productos participantes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="promo-pie">
                        Subtotal: <strong>Bs. {{ number_format($promocion->subtotalCombo(), 2) }}</strong>
                        &nbsp;·&nbsp; Total en promoción: <strong>Bs. {{ number_format($promocion->totalCombo(), 2) }}</strong>
                        &nbsp;·&nbsp; Ahorro: <span class="ahorro">Bs. {{ number_format($promocion->descuentoCombo(), 2) }}</span>
                    </div>
                </div>
            @empty
                <div class="promo">
                    <div class="promo-pie" style="text-align:center;">
                        No hay promociones vigentes en {{ $sucursal->nombre }} para el período indicado.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="izq" style="width:33%">
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Promociones
                </td>
                <td class="cen" style="width:34%">
                    Período <strong>{{ $desde->format('d/m/Y') }} → {{ $hasta->format('d/m/Y') }}</strong>
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
