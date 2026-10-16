<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Ficha de Sucursal · {{ $sucursal->nombre }}</title>
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
            padding: 12px 14px;
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

        .topbar .sucursal {
            font-size: 11px;
            color: #cfd4dc;
        }

        .topbar h1 {
            font-size: 17px;
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
            padding: 10px 12px 6px;
        }

        table.ficha {
            width: 100%;
            margin: 0 auto 8px;
            border-collapse: collapse;
        }

        table.ficha td {
            width: 25%;
            padding: 8px 8px;
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
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #8a91a0;
            margin-bottom: 1px;
        }

        .ficha .valor {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #1f2430;
        }

        .ficha .subvalor {
            display: block;
            font-size: 7px;
            color: #8a91a0;
            margin-top: 1px;
        }

        .seccion-titulo {
            font-size: 9px;
            font-weight: bold;
            color: #1f2430;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 6px 0 5px;
            border-bottom: 1.5px solid #e8c15c;
            padding-bottom: 3px;
        }

        table.info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.info-grid td {
            padding: 4px 6px;
            vertical-align: top;
            border: 1px solid #e3e6ea;
        }

        table.info-grid td.label {
            width: 16%;
            background: #f7f8fa;
            font-size: 7.5px;
            font-weight: bold;
            color: #5b626e;
            text-transform: uppercase;
        }

        table.info-grid td.value {
            width: 34%;
            font-size: 8.5px;
            color: #1f2430;
        }

        table.detalle {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table.detalle thead th {
            background: #1f2430;
            color: #fff;
            padding: 4px 6px;
            font-size: 7px;
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
            padding: 3px 6px;
            border-bottom: 1px solid #e3e6ea;
            font-size: 7.5px;
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

        .columnas {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .columnas td {
            width: 33.33%;
            vertical-align: top;
            padding: 0 4px;
        }

        .columnas td:first-child {
            padding-left: 0;
        }

        .columnas td:last-child {
            padding-right: 0;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f7f8fa;
            border-top: 2px solid #e8c15c;
            padding: 5px 12px;
            font-size: 7px;
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

        .sin-datos {
            text-align: center;
            color: #8a91a0;
            font-size: 7.5px;
            padding: 6px;
        }

        @page {
            margin: 10mm 10mm 14mm 10mm;
        }
    </style>
</head>

<body>
    @php
        $horariosActivos = $sucursal->horarios->where('activo', true);
        $mesasActivas = $sucursal->mesas->where('estado', true);
        $cajasActivas = $sucursal->cajas->where('activo', true);
        $empleadosActivos = $sucursal->empleados->where('estado', true);
    @endphp

    <div class="hoja">
        <div class="topbar">
            <table>
                <tr>
                    <td style="width:30%">
                        <div class="marca">{{ $configuracionSitio->get('SITIO_NOMBRE') }}</div>
                        <div class="sucursal">Ficha de sucursal</div>
                    </td>
                    <td style="width:40%">
                        <h1>{{ $sucursal->nombre }}</h1>
                    </td>
                    <td style="width:30%">
                        <div class="fecha-cabecera">
                            <strong>{{ now()->format('d/m/Y H:i') }}</strong>
                            Generación de ficha
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
                        <span class="etiqueta">Horarios activos</span>
                        <span class="valor">{{ $horariosActivos->count() }}</span>
                        <span class="subvalor">Días de atención</span>
                    </td>
                    <td class="destacado">
                        <span class="etiqueta">Mesas activas</span>
                        <span class="valor">{{ $mesasActivas->count() }}</span>
                        <span class="subvalor">Capacidad total</span>
                    </td>
                    <td>
                        <span class="etiqueta">Cajas activas</span>
                        <span class="valor">{{ $cajasActivas->count() }}</span>
                        <span class="subvalor">Puntos de pago</span>
                    </td>
                    <td>
                        <span class="etiqueta">Empleados activos</span>
                        <span class="valor">{{ $empleadosActivos->count() }}</span>
                        <span class="subvalor">Personal asignado</span>
                    </td>
                </tr>
            </table>

            <div class="seccion-titulo">Datos generales</div>
            <table class="info-grid">
                <tr>
                    <td class="label">Nombre</td>
                    <td class="value">{{ $sucursal->nombre }}</td>
                    <td class="label">Teléfono</td>
                    <td class="value">{{ $sucursal->telefono }}</td>
                </tr>
                <tr>
                    <td class="label">Dirección</td>
                    <td class="value">{{ $sucursal->direccion }}</td>
                    <td class="label">Gerente</td>
                    <td class="value">
                        @if ($sucursal->gerente)
                            {{ $sucursal->gerente->nombre }} {{ $sucursal->gerente->apellido }}
                        @else
                            Sin gerente asignado
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Coordenadas</td>
                    <td class="value" colspan="3">
                        {{ number_format((float) $sucursal->latitud, 6) }}, {{ number_format((float) $sucursal->longitud, 6) }}
                    </td>
                </tr>
            </table>

            <div class="seccion-titulo">Horarios de atención activos</div>
            <table class="detalle">
                <thead>
                    <tr>
                        <th class="text-start">Día</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($horariosActivos->sortBy('diasemana') as $horario)
                        <tr>
                            <td class="text-start">{{ App\Models\HorarioSucursal::nombreDia($horario->diasemana) }}</td>
                            <td>{{ substr($horario->horaapertura, 0, 5) }}</td>
                            <td>{{ substr($horario->horacierre, 0, 5) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="sin-datos">Sin horarios activos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="columnas">
                <tr>
                    <td>
                        <div class="seccion-titulo">Mesas activas</div>
                        <table class="detalle">
                            <thead>
                                <tr>
                                    <th class="text-start">Mesa</th>
                                    <th class="num">Cap.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mesasActivas->sortBy('numeromesa')->values() as $mesa)
                                    <tr>
                                        <td class="text-start">Mesa {{ $mesa->numeromesa }}</td>
                                        <td class="num">{{ $mesa->capacidad }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="sin-datos">Sin mesas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <div class="seccion-titulo">Cajas activas</div>
                        <table class="detalle">
                            <thead>
                                <tr>
                                    <th class="text-start">Nombre</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cajasActivas->values() as $caja)
                                    <tr>
                                        <td class="text-start">{{ $caja->nombre }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="sin-datos">Sin cajas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <div class="seccion-titulo">Empleados activos</div>
                        @php
                            $prioridadRol = ['GERENTE' => 1, 'CAJERO' => 2, 'MESERO' => 3];
                            $empleadosOrdenados = $empleadosActivos
                                ->sortBy(fn ($e) => $prioridadRol[$e->rol?->claverol] ?? 99)
                                ->values();
                        @endphp
                        <table class="detalle">
                            <thead>
                                <tr>
                                    <th class="text-start">Nombre</th>
                                    <th>Rol</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($empleadosOrdenados as $empleado)
                                    <tr>
                                        <td class="text-start">{{ $empleado->nombre }} {{ $empleado->apellido }}</td>
                                        <td>{{ $empleado->rol?->nombrerol ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="sin-datos">Sin empleados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td class="izq" style="width:33%">
                    <strong>Fuente:</strong> {{ $configuracionSitio->get('SITIO_NOMBRE') }} · Sucursales
                </td>
                <td class="cen" style="width:34%">
                    Generado el <strong>{{ now()->format('d/m/Y') }}</strong> a las
                    <strong>{{ now()->format('H:i') }}</strong>
                </td>
                <td class="der" style="width:33%">
                    Ficha generada automáticamente
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
