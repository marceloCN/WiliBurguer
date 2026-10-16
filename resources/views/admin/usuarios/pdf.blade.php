<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Informe de Auditoría · Usuarios</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2430;
            line-height: 1.4;
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
            padding: 6px 7px;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: center;
        }

        table.detalle thead th.text-start {
            text-align: left;
        }

        table.detalle thead th.num {
            text-align: right;
        }

        table.detalle tbody td {
            padding: 5px 7px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 8px;
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
            min-width: 42px;
            padding: 2px 5px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .bg-success { background: #d4edda; color: #155724; }
        .bg-danger { background: #f8d7da; color: #721c24; }
        .bg-warning { background: #fff3cd; color: #856404; }
        .bg-secondary { background: #e2e3e5; color: #383d41; }

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
            margin: 12mm 12mm 16mm 12mm;
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
                        <div class="subtitulo">Auditoría de usuarios</div>
                    </td>
                    <td style="width:40%">
                        <h1>Informe de usuarios</h1>
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
                        <span class="valor">{{ $empleados->count() }}</span>
                        <span class="subvalor">Empleados en el informe</span>
                    </td>
                    <td class="destacado">
                        <span class="etiqueta">Generado por</span>
                        <span class="valor">{{ auth()->user()->empleado->nombre }} {{ auth()->user()->empleado->apellido }}</span>
                        <span class="subvalor">{{ auth()->user()->empleado->rol?->nombrerol }}</span>
                    </td>
                    <td>
                        <span class="etiqueta">Estado</span>
                        <span class="valor">{{ $filtros['estado'] ?: 'Todos' }}</span>
                        <span class="subvalor">Filtro aplicado</span>
                    </td>
                    <td>
                        <span class="etiqueta">Sucursal</span>
                        <span class="valor">{{ $filtros['sucursal'] ?: ($sucursalGerente?->nombre ?? 'Todas') }}</span>
                        <span class="subvalor">Alcance del informe</span>
                    </td>
                </tr>
            </table>

            <div class="seccion-titulo">Filtros aplicados</div>
            <table class="filtros">
                <tr>
                    <td class="label">Búsqueda</td>
                    <td class="value">{{ $filtros['busqueda'] ?: 'Ninguno' }}</td>
                    <td class="label">Rol</td>
                    <td class="value">{{ $filtros['rol'] ?: 'Todos' }}</td>
                </tr>
            </table>

            <div class="seccion-titulo">Detalle de empleados</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th class="num" style="width:5%">#</th>
                        <th class="text-start" style="width:20%">Empleado</th>
                        <th class="text-start" style="width:14%">Usuario</th>
                        <th class="text-start" style="width:18%">Correo</th>
                        <th style="width:12%">Rol</th>
                        <th style="width:13%">Sucursal</th>
                        <th style="width:10%">Estado</th>
                        <th style="width:13%">Último acceso</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empleados as $i => $empleado)
                        <tr>
                            <td class="num">{{ $i + 1 }}</td>
                            <td class="text-start"><strong>{{ $empleado->nombre }} {{ $empleado->apellido }}</strong></td>
                            <td class="text-start">{{ $empleado->cuentaUsuario?->usuario ?: '—' }}</td>
                            <td class="text-start">{{ $empleado->cuentaUsuario?->correo ?: '—' }}</td>
                            <td>{{ $empleado->rol?->nombrerol ?: '—' }}</td>
                            <td>{{ $empleado->sucursal?->nombre ?: '—' }}</td>
                            <td>
                                @if (! $empleado->cuentaUsuario)
                                    <span class="badge bg-secondary">Sin cuenta</span>
                                @elseif ($empleado->estado && ! $empleado->cuentaUsuario->bloqueado)
                                    <span class="badge bg-success">Activo</span>
                                @elseif (! $empleado->estado)
                                    <span class="badge bg-danger">Inactivo</span>
                                @else
                                    <span class="badge bg-warning">Bloqueado</span>
                                @endif
                            </td>
                            <td>{{ $empleado->cuentaUsuario?->ultimoacceso?->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="sin-datos">No hay empleados registrados.</td>
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
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Usuarios
                </td>
                <td class="cen" style="width:34%">
                    Generado el <strong>{{ now()->format('d/m/Y') }}</strong> a las
                    <strong>{{ now()->format('H:i') }}</strong>
                </td>
                <td class="der" style="width:33%">
                    Informe generado automáticamente
                    @if ($sucursalGerente)
                        · {{ $sucursalGerente->nombre }}
                    @endif
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
