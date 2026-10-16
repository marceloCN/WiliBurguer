<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Estado de inventario · {{ $sucursal->nombre }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2430;
            line-height: 1.45;
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
            padding: 14px 16px 4px;
        }

        table.ficha {
            width: 96%;
            margin: 0 auto 12px;
            border-collapse: collapse;
        }

        table.ficha td {
            width: 25%;
            padding: 10px 12px;
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

        /* ======= Títulos de sección ======= */
        .seccion-titulo {
            font-size: 11px;
            font-weight: bold;
            color: #1f2430;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 8px auto 10px;
            width: 96%;
            border-bottom: 2px solid #e8c15c;
            padding-bottom: 5px;
        }

        /* ======= Gráfico de barras ======= */
        table.grafico {
            width: 92%;
            margin: 0 auto 6px;
            border-collapse: collapse;
        }

        table.grafico .fila-barra td {
            padding: 3px 4px;
            vertical-align: middle;
        }

        table.grafico .g-nombre {
            width: 22%;
            font-weight: bold;
            font-size: 9.5px;
            text-align: right;
            padding-right: 10px;
        }

        table.grafico .g-pista {
            width: 44%;
            height: 15px;
            background: #f1f3f6;
            border: 1px solid #d9dde3;
            border-radius: 8px;
            padding: 0 !important;
            overflow: hidden;
        }

        table.grafico .barra {
            height: 13px;
            border-radius: 7px;
            background: #2f8f6b;
        }

        table.grafico .barra.alta {
            background: #c29b40;
        }

        table.grafico .barra.baja {
            background: #d97742;
        }

        table.grafico .g-valor {
            width: 13%;
            text-align: right;
            font-weight: bold;
            font-size: 9.5px;
            padding-left: 10px;
        }

        table.grafico .g-porcentaje {
            width: 12%;
            text-align: left;
            color: #8a91a0;
            font-size: 8.5px;
            padding-left: 6px;
        }

        table.grafico .g-unidad {
            width: 9%;
            text-align: left;
            color: #8a91a0;
            font-size: 9px;
        }

        .leyenda {
            width: 92%;
            margin: 0 auto 12px;
            text-align: center;
            font-size: 8.5px;
            color: #6c7480;
        }

        .leyenda span {
            margin: 0 8px;
            white-space: nowrap;
        }

        .cuadro {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 2px;
            margin-right: 3px;
        }

        .cuadro.verde {
            background: #2f8f6b;
        }

        .cuadro.gold {
            background: #c29b40;
        }

        .cuadro.naranja {
            background: #d97742;
        }

        /* ======= Tabla de detalle ======= */
        table.detalle {
            width: 96%;
            margin: 0 auto 8px;
            border-collapse: collapse;
        }

        table.detalle thead th {
            background: #1f2430;
            color: #fff;
            padding: 8px 10px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: center;
        }

        table.detalle thead th.num {
            text-align: right;
        }

        table.detalle tbody td {
            padding: 8px 10px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 10px;
            text-align: center;
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
            padding: 9px 10px;
            background: #f0f1f4;
            font-weight: bold;
            border-top: 2px solid #1f2430;
            text-align: center;
        }

        table.detalle tfoot td.num {
            text-align: right;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .unidad {
            color: #8a91a0;
            font-size: 9px;
        }

        .negativo {
            color: #c0392b;
            font-weight: bold;
        }

        .positivo {
            color: #1d7a55;
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
                        <h1>Estado actual de inventario</h1>
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
                        <span class="etiqueta">Insumos registrados</span>
                        <span class="valor">{{ $inventario->count() }}</span>
                        <span class="subvalor">En el inventario actual</span>
                    </td>
                    <td>
                        <span class="etiqueta">Generado por</span>
                        <span class="valor">{{ $empleado->nombre.' '.$empleado->apellido }}</span>
                        <span class="subvalor">Rol: {{ $empleado->rol?->claverol }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Total en stock</span>
                        <span class="valor">{{ rtrim(rtrim(number_format($inventario->sum(fn ($i) => (float) $i->cantidad), 3, '.', ''), '0'), '.') }}</span>
                        <span class="subvalor">Suma de cantidades</span>
                    </td>
                </tr>
            </table>

            @php
                $max = max($maxCantidad, 1);
                $fmt = fn ($n) => rtrim(rtrim(number_format($n, 3, '.', ''), '0'), '.');
            @endphp

            <div class="seccion-titulo">Cantidad de stock por insumo</div>
            <table class="grafico">
                @foreach ($inventario as $item)
                    @php
                        $cantidad = (float) $item->cantidad;
                        $porcentaje = $cantidad > 0 ? ($cantidad / $max) * 100 : 0;
                        $porcentajeAncho = $porcentaje > 0 ? max(3, round($porcentaje, 1)) : 0;
                        $tipoClase = $porcentaje > 66 ? ' alta' : ($porcentaje < 8 && $cantidad > 0 ? ' baja' : '');
                    @endphp
                    <tr class="fila-barra">
                        <td class="g-nombre">{{ $item->insumo?->nombre }}</td>
                        <td class="g-pista">
                            <div class="barra{{ $tipoClase }}" style="width: {{ $porcentajeAncho }}%;"></div>
                        </td>
                        <td class="g-valor">{{ $fmt($cantidad) }}</td>
                        <td class="g-porcentaje">{{ $porcentaje > 0 ? number_format($porcentaje, 1).' %' : '—' }}</td>
                        <td class="g-unidad">{{ $item->insumo?->unidadMedida?->abreviatura ?? '' }}</td>
                    </tr>
                @endforeach
            </table>
            <div class="leyenda">
                <span><span class="cuadro verde"></span>En stock</span>
                <span><span class="cuadro gold"></span>&ge; 66% del máximo</span>
                <span><span class="cuadro naranja"></span>Stock mínimo</span>
            </div>

            <div class="seccion-titulo">Detalle del inventario</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th style="width:38%" class="encabezado-insumo">Insumo</th>
                        <th style="width:12%">Unidad</th>
                        <th style="width:18%" class="num">Cantidad</th>
                        <th style="width:16%" class="num">Mínimo</th>
                        <th style="width:16%" class="num">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventario as $item)
                        @php
                            $cantidad = (float) $item->cantidad;
                            $minimo = $item->cantidadminima !== null ? (float) $item->cantidadminima : null;
                            $diferencia = $minimo !== null ? $cantidad - $minimo : null;
                        @endphp
                        <tr>
                            <td class="insumo">{{ $item->insumo?->nombre }}</td>
                            <td><span class="unidad">{{ $item->insumo?->unidadMedida?->abreviatura ?? '—' }}</span></td>
                            <td class="num"><b>{{ $fmt($cantidad) }}</b></td>
                            <td class="num">{{ $minimo !== null ? $fmt($minimo) : '—' }}</td>
                            <td class="num {{ $diferencia !== null ? ($diferencia < 0 ? 'negativo' : 'positivo') : '' }}">
                                {{ $diferencia !== null ? $fmt($diferencia) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay insumos cargados en esta sucursal.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-end">Total</td>
                        <td></td>
                        <td class="num">{{ $fmt($inventario->sum(fn ($i) => (float) $i->cantidad)) }}</td>
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