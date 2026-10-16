<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Bitácora de Acciones</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #1f2430;
            line-height: 1.35;
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
            padding: 14px 16px;
        }

        .topbar table {
            width: 100%;
            border-collapse: collapse;
        }

        .topbar .marca {
            font-size: 10px;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #e8c15c;
            font-weight: bold;
        }

        .topbar .subtitulo {
            font-size: 11px;
            color: #cfd4dc;
        }

        .topbar h1 {
            font-size: 18px;
            margin-top: 2px;
            text-align: center;
        }

        .topbar .fecha-cabecera {
            text-align: right;
            font-size: 8px;
            color: #cfd4dc;
        }

        .topbar .fecha-cabecera strong {
            display: block;
            color: #fff;
            font-size: 10px;
        }

        .separador {
            height: 3px;
            background: #e8c15c;
        }

        .bloque {
            padding: 12px 14px 6px;
        }

        table.ficha {
            width: 100%;
            margin: 0 auto 10px;
            border-collapse: collapse;
        }

        table.ficha td {
            width: 25%;
            padding: 9px 10px;
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
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #8a91a0;
            margin-bottom: 2px;
        }

        .ficha .valor {
            display: block;
            font-size: 13px;
            font-weight: bold;
            color: #1f2430;
        }

        .ficha .subvalor {
            display: block;
            font-size: 8px;
            color: #8a91a0;
            margin-top: 2px;
        }

        .seccion-titulo {
            font-size: 10px;
            font-weight: bold;
            color: #1f2430;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 8px 0 6px;
            border-bottom: 1.5px solid #e8c15c;
            padding-bottom: 4px;
        }

        table.filtros {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.filtros td {
            padding: 5px 8px;
            border: 1px solid #e3e6ea;
            vertical-align: top;
            font-size: 8.5px;
        }

        table.filtros td.label {
            width: 16%;
            background: #f7f8fa;
            font-weight: bold;
            color: #5b626e;
            text-transform: uppercase;
            font-size: 7.5px;
        }

        table.filtros td.value {
            width: 34%;
            color: #1f2430;
        }

        table.detalle {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.detalle thead th {
            background: #1f2430;
            color: #fff;
            padding: 5px 5px;
            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
        }

        table.detalle thead th.text-start {
            text-align: left;
        }

        table.detalle thead th.num {
            text-align: right;
        }

        table.detalle tbody td {
            padding: 4px 5px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 7px;
            text-align: center;
            vertical-align: middle;
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

        .badge {
            display: inline-block;
            min-width: 36px;
            padding: 2px 4px;
            border-radius: 6px;
            font-size: 6.5px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .bg-primary { background: #cfe2ff; color: #084298; }
        .bg-secondary { background: #e2e3e5; color: #41464b; }
        .bg-success { background: #d4edda; color: #155724; }
        .bg-danger { background: #f8d7da; color: #721c24; }
        .bg-warning { background: #fff3cd; color: #856404; }
        .bg-dark { background: #d6d8d9; color: #1b1e21; }

        .sin-datos {
            text-align: center;
            color: #8a91a0;
            font-size: 8px;
            padding: 8px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f7f8fa;
            border-top: 2px solid #e8c15c;
            padding: 6px 14px;
            font-size: 7.5px;
            color: #5b626e;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            vertical-align: middle;
        }

        .footer .izq { text-align: left; }
        .footer .cen { text-align: center; }
        .footer .der { text-align: right; }
        .footer strong { color: #1f2430; }

        @page {
            margin: 12mm 10mm 16mm 10mm;
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
                        <div class="subtitulo">Seguridad</div>
                    </td>
                    <td style="width:40%">
                        <h1>Bitácora de acciones</h1>
                    </td>
                    <td style="width:30%">
                        <div class="fecha-cabecera">
                            <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                            Generación del informe
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
                        <span class="etiqueta">Total registros</span>
                        <span class="valor">{{ $acciones->count() }}</span>
                        <span class="subvalor">Acciones en el informe</span>
                    </td>
                    <td class="destacado">
                        <span class="etiqueta">Generado por</span>
                        <span class="valor">{{ auth()->user()->empleado->nombre }} {{ auth()->user()->empleado->apellido }}</span>
                        <span class="subvalor">{{ auth()->user()->empleado->rol?->nombrerol }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Usuario</span>
                        <span class="valor">{{ $filtros['empleado'] ?: 'Todos' }}</span>
                        <span class="subvalor">Filtro aplicado</span>
                    </td>
                    <td>
                        <span class="etiqueta">Sucursal</span>
                        <span class="valor">{{ $filtros['sucursal'] ?: 'Todas' }}</span>
                        <span class="subvalor">Filtro aplicado</span>
                    </td>
                </tr>
            </table>

            <div class="seccion-titulo">Filtros aplicados</div>
            <table class="filtros">
                <tr>
                    <td class="label">Desde</td>
                    <td class="value">{{ $filtros['desde'] ?: 'Sin límite' }}</td>
                    <td class="label">Hasta</td>
                    <td class="value">{{ $filtros['hasta'] ?: 'Sin límite' }}</td>
                </tr>
            </table>

            <div class="seccion-titulo">Detalle de acciones</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th class="num" style="width:4%">#</th>
                        <th style="width:11%">Fecha y hora</th>
                        <th class="text-start" style="width:14%">Empleado</th>
                        <th class="text-start" style="width:13%">Permiso</th>
                        <th style="width:8%">Acción</th>
                        <th style="width:9%">Tabla</th>
                        <th class="num" style="width:6%">Reg.</th>
                        <th class="text-start" style="width:12%">Equipo</th>
                        <th style="width:8%">IP</th>
                        <th style="width:9%">Resultado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($acciones as $i => $accion)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td>{{ $accion->fechahora->format('d/m/Y H:i:s') }}</td>
                            <td class="text-start">
                                @if ($accion->empleado)
                                    <strong>{{ $accion->empleado->nombre }} {{ $accion->empleado->apellido }}</strong>
                                @else
                                    Empleado no registrado
                                @endif
                            </td>
                            <td class="text-start">{{ $accion->permiso?->nombre ?? '—' }}</td>
                            <td>
                                @if ($accion->accion === 'CREATE')
                                    <span class="badge bg-success">CREATE</span>
                                @elseif ($accion->accion === 'UPDATE')
                                    <span class="badge bg-primary">UPDATE</span>
                                @elseif ($accion->accion === 'DELETE')
                                    <span class="badge bg-danger">DELETE</span>
                                @else
                                    <span class="badge bg-dark">{{ $accion->accion }}</span>
                                @endif
                            </td>
                            <td>{{ $accion->tablaafectada ?? '—' }}</td>
                            <td class="num">{{ $accion->idregistroafectado ?? '—' }}</td>
                            <td class="text-start">{{ $accion->equipo ?? '—' }}</td>
                            <td>{{ $accion->ip ?? '—' }}</td>
                            <td>
                                @if ($accion->resultado === 'EXITOSO')
                                    <span class="badge bg-success">EXITOSO</span>
                                @elseif ($accion->resultado === 'FALLIDO')
                                    <span class="badge bg-danger">FALLIDO</span>
                                @else
                                    <span class="badge bg-warning">{{ $accion->resultado }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="sin-datos">No hay acciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="izq" style="width:33%">
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Bitácora de acciones
                </td>
                <td class="cen" style="width:34%">
                    Generado el <strong>{{ now()->format('d/m/Y') }}</strong> a las
                    <strong>{{ now()->format('H:i') }}</strong>
                </td>
                <td class="der" style="width:33%">
                    Informe generado automáticamente
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
