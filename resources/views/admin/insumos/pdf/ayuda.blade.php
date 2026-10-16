<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Guía de insumos y unidades de medida</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #1f2430;
            line-height: 1.45;
        }

        .hoja {
            width: 100%;
            border: 1px solid #d9dde3;
            border-radius: 8px;
            overflow: hidden;
        }

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

        .bloque {
            padding: 14px 16px 6px;
        }

        table.ficha {
            width: 100%;
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

        .intro {
            background: #f7f8fa;
            border-left: 4px solid #e8c15c;
            padding: 10px 14px;
            margin: 0 auto 14px;
            border-radius: 0 6px 6px 0;
            font-size: 9px;
            color: #5b626e;
        }

        table.detalle {
            width: 100%;
            margin: 0 auto 8px;
            border-collapse: collapse;
        }

        table.detalle thead th {
            background: #1f2430;
            color: #fff;
            padding: 8px 10px;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            text-align: center;
        }

        table.detalle thead th.text-start {
            text-align: left;
        }

        table.detalle thead th.num {
            text-align: right;
        }

        table.detalle tbody td {
            padding: 7px 9px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 9px;
            text-align: center;
            vertical-align: top;
        }

        table.detalle tbody td.text-start {
            text-align: left;
        }

        table.detalle tbody td.num {
            text-align: right;
        }

        table.detalle tbody tr:nth-child(even) {
            background: #f7f8fa;
        }

        .unidad {
            color: #8a91a0;
            font-size: 8.5px;
        }

        .estado {
            display: inline-block;
            min-width: 42px;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .estado.activo {
            background: #d4edda;
            color: #155724;
        }

        .estado.inactivo {
            background: #f8d7da;
            color: #721c24;
        }

        .recomendaciones {
            margin-bottom: 14px;
        }

        .recomendacion {
            margin-bottom: 8px;
            padding: 8px 10px;
            background: #f7f8fa;
            border: 1px solid #e3e6ea;
            border-radius: 6px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .recomendacion h3 {
            font-size: 9.5px;
            color: #1f2430;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .recomendacion p {
            font-size: 8.5px;
            color: #5b626e;
            margin-bottom: 2px;
        }

        .recomendacion .nota {
            font-size: 8px;
            color: #8a91a0;
            font-style: italic;
        }

        .pasos {
            background: #f7f8fa;
            border: 1px solid #e3e6ea;
            padding: 12px 16px;
            border-radius: 6px;
        }

        .pasos ol {
            margin: 0;
            padding-left: 18px;
        }

        .pasos li {
            margin-bottom: 5px;
            font-size: 9px;
            color: #5b626e;
        }

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
                        <div class="sucursal">Insumos y unidades de medida</div>
                    </td>
                    <td style="width:40%">
                        <h1>Guía de insumos</h1>
                    </td>
                    <td style="width:30%">
                        <div class="fecha-cabecera">
                            <strong>{{ $generado->format('d/m/Y H:i') }}</strong>
                            Generación de la guía
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
                        <span class="etiqueta">Unidades activas</span>
                        <span class="valor">{{ $unidades->count() }}</span>
                        <span class="subvalor">Tipos de medida disponibles</span>
                    </td>
                    <td class="destacado">
                        <span class="etiqueta">Insumos activos</span>
                        <span class="valor">{{ $insumos->count() }}</span>
                        <span class="subvalor">Disponibles para inventario</span>
                    </td>
                    <td>
                        <span class="etiqueta">Generado por</span>
                        <span class="valor">{{ $empleado->nombre.' '.$empleado->apellido }}</span>
                        <span class="subvalor">Rol: {{ $empleado->rol?->claverol }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Tipos de unidad</span>
                        <span class="valor">{{ $unidades->unique('tipo')->count() }}</span>
                        <span class="subvalor">Categorías configuradas</span>
                    </td>
                </tr>
            </table>

            <div class="intro">
                Esta guía te ayuda a identificar qué unidad de medida asignar a cada insumo para que el inventario sea consistente, claro y fácil de usar.
            </div>

            <div class="seccion-titulo">Unidades de medida registradas</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th class="text-start" style="width:45%">Nombre</th>
                        <th style="width:25%">Abreviatura</th>
                        <th style="width:30%">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($unidades as $unidad)
                        <tr>
                            <td class="text-start"><strong>{{ $unidad->nombre }}</strong></td>
                            <td>{{ $unidad->abreviatura }}</td>
                            <td>{{ $unidad->tipo }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No hay unidades de medida activas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="seccion-titulo">Insumos registrados</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th class="text-start" style="width:30%">Insumo</th>
                        <th style="width:24%">Unidad de medida</th>
                        <th style="width:12%">Abrev.</th>
                        <th class="text-start" style="width:34%">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($insumos as $insumo)
                        <tr>
                            <td class="text-start"><strong>{{ $insumo->nombre }}</strong></td>
                            <td>{{ $insumo->unidadMedida?->nombre ?? '—' }}</td>
                            <td><span class="unidad">{{ $insumo->unidadMedida?->abreviatura ?? '—' }}</span></td>
                            <td class="text-start">{{ $insumo->descripcion ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No hay insumos activos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="seccion-titulo">Recomendaciones por tipo de unidad</div>
            <div class="recomendaciones">
                <div class="recomendacion">
                    <h3>Peso</h3>
                    <p>Usa <strong>kg</strong> o <strong>g</strong> para carnes, verduras, quesos, granos y cualquier producto que se pese.</p>
                    <p class="nota">Ejemplos: carne molida, tomate, queso rallado, harina.</p>
                </div>
                <div class="recomendacion">
                    <h3>Volumen</h3>
                    <p>Usa <strong>l</strong> o <strong>ml</strong> para líquidos y salsas.</p>
                    <p class="nota">Ejemplos: aceite, salsa de tomate, vinagre, refrescos.</p>
                </div>
                <div class="recomendacion">
                    <h3>Unidad / pieza</h3>
                    <p>Usa <strong>und</strong>, <strong>pza</strong> o <strong>docena</strong> cuando se cuentan elementos individuales.</p>
                    <p class="nota">Ejemplos: huevos, panes, empaques, cubiertos desechables.</p>
                </div>
                <div class="recomendacion">
                    <h3>Longitud</h3>
                    <p>Usa <strong>m</strong> o <strong>cm</strong> solo cuando sea necesario.</p>
                    <p class="nota">Ejemplos: rollos de papel, mangueras, cables.</p>
                </div>
            </div>

            <div class="seccion-titulo">Cómo crear un insumo paso a paso</div>
            <div class="pasos">
                <ol>
                    <li>Ve a la sección <strong>Insumos y unidades de medida</strong>.</li>
                    <li>Crea la unidad de medida correspondiente con el botón <strong>+ Unidad</strong>.</li>
                    <li>Crea el insumo con el botón <strong>+ Insumo</strong> y selecciona la unidad creada.</li>
                    <li>El insumo quedará disponible para registrar entradas, salidas y ajustes en el inventario de cualquier sucursal.</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="izq" style="width:33%">
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Insumos
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
