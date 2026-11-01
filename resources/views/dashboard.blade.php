@extends('layouts.app')

@section('title', 'Panel de administracion · '.$configuracionSitio->get('SITIO_NOMBRE'))

@php
    $nombresDias = [
        'Sunday' => 'Domingo',
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miercoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sabado',
    ];
    $fechaHoy = now()->format('d/m/Y');
    $diaHoy = $nombresDias[now()->format('l')] ?? now()->format('l');

    // Orden en que se presentan los grupos de modulos del sistema.
    $gruposModulos = [
        'operacion' => 'Operacion del dia',
        'catalogo' => 'Catalogo y promociones',
        'inventario' => 'Inventario e insumos',
        'administracion' => 'Administracion del sistema',
    ];
@endphp

@section('content')
    <div class="container py-4">
        {{-- Encabezado de bienvenida --}}
        <div class="card tarjeta-producto border-0 mb-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="display-5">{{
                                match ($rol->claverol) {
                                    'ADMINISTRADOR' => '👨‍💼',
                                    'GERENTE' => '📊',
                                    'CAJERO' => '💰',
                                    'MESERO' => '🍽️',
                                    default => '👤',
                                }
                            }}</span>
                            <div>
                                <h1 class="h3 fw-bold mb-0">
                                    ¡Hola, {{ $empleado->nombre }}!
                                </h1>
                                <p class="text-muted mb-0">
                                    {{ $diaHoy }} {{ $fechaHoy }} · Rol:
                                    <span class="badge text-bg-brand">{{ $rol->claverol }}</span>
                                </p>
                            </div>
                        </div>
                        <p class="mb-0 lead" style="font-size: 1.05rem;">
                            @switch($rol->claverol)
                                @case('ADMINISTRADOR')
                                    Tienes el control total del sistema. Desde aqui puedes gestionar sucursales, empleados, configuracion y consultar el estado general del restaurante.
                                    @break
                                @case('GERENTE')
                                    Aqui tienes un resumen de tus sucursales a cargo, empleados, pedidos y ventas del dia.
                                    @break
                                @case('CAJERO')
                                    Revisa el estado de tu caja, las ventas del turno y los pedidos pendientes de cobro.
                                    @break
                                @case('MESERO')
                                    Desde aqui puedes ver las mesas disponibles y acceder rapidamente a tomar un nuevo pedido.
                                    @break
                                @default
                                    Bienvenido al panel de administracion del sistema.
                            @endswitch
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        {{-- Accesos directos: solo segun los modulos (permisos) --}}
                        @if (isset($modulos['pedidos']))
                            <a href="{{ route('dashboard.generar-pedidos.index') }}" class="btn btn-brand btn-lg mb-2 w-100 w-lg-auto">
                                🧾 Generar pedido
                            </a>
                        @endif
                        @if (isset($modulos['caja']))
                            <a href="{{ route('dashboard.caja.index') }}" class="btn btn-outline-brand w-100 w-lg-auto">
                                💰 Ir a caja
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('partials.mensajes')

        @if ($sinSucursal)
            <div class="alert alert-warning shadow-sm d-flex align-items-center gap-3">
                <span class="fs-4">⏳</span>
                <div>
                    <strong>No estas añadido en el sistema a ninguna sucursal.</strong><br>
                    Tu usuario no esta vinculado a una sucursal, por lo que no se muestran los menus del panel.
                    Contacta al administrador para que te asigne a una sucursal y habilite tus accesos.
                </div>
            </div>
        @else
            @if ($avisoCajaCerrada)
                <div class="alert alert-warning shadow-sm d-flex align-items-center gap-3 mb-4" role="alert">
                    <span class="fs-4">⚠️</span>
                    <div>
                        <strong>Tu sucursal no tiene una caja abierta.</strong><br>
                        Generar pedidos requiere que el turno este abierto: un cajero o gerente con permiso de caja
                        debe realizar la apertura primero. Cuando haya una apertura activa entraras directo a tomar pedidos.
                    </div>
                </div>
            @endif

            {{-- Estadisticas por rol --}}
            @if (! empty($estadisticas))
                <h2 class="h5 fw-bold mb-3">Resumen del dia</h2>
                <div class="row g-3 mb-4">
                    @foreach ($estadisticas as $stat)
                        <div class="col-6 col-md-4 col-lg-3 col-xxl-2">
                            <div class="card tarjeta-producto h-100">
                                <div class="card-body d-flex align-items-center gap-3">
                                    <span class="badge {{ $stat['color'] }} p-2 rounded-3 fs-5">{{ $stat['icono'] }}</span>
                                    <div class="min-w-0">
                                        <h3 class="h5 fw-bold mb-0 text-truncate">
                                            {{ $stat['valor'] }}
                                        </h3>
                                        <p class="text-muted small mb-0">{{ $stat['titulo'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Modulos del sistema: en funcion de los permisos del rol.
                 Sin permisos no se visualiza ningun menu. --}}
            @if (! empty($modulos))
                @foreach ($gruposModulos as $claveGrupo => $tituloGrupo)
                    @php($modulosDelGrupo = collect($modulos)->where('grupo', $claveGrupo))
                    @if ($modulosDelGrupo->isNotEmpty())
                        <h2 class="h5 fw-bold mb-3">{{ $tituloGrupo }}</h2>
                        <div class="row g-3 mb-4">
                            @foreach ($modulosDelGrupo as $clave => $modulo)
                                @if ($modulo['activo'] && $modulo['ruta'])
                                    <div class="col-12 col-sm-6 col-lg-4 col-xxl-3">
                                        <a href="{{ route($modulo['ruta'], $modulo['parametros'] ?? []) }}"
                                           class="card tarjeta-producto h-100 text-decoration-none">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-3 mb-2">
                                                    <span class="badge {{ $modulo['color'] }} p-3 rounded-3 fs-4">{{ $modulo['icono'] }}</span>
                                                    <h3 class="h6 fw-bold mb-0 text-dark">{{ $modulo['titulo'] }}</h3>
                                                </div>
                                                <p class="text-muted small mb-0">{{ $modulo['descripcion'] }}</p>
                                            </div>
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @else
                <div class="alert alert-info shadow-sm">
                    <strong>Sin modulos habilitados.</strong><br>
                    Tu rol no tiene permisos para mostrar menus en este panel. El administrador puede asignarte
                    los permisos desde la gestion de permisos del sistema.
                </div>
            @endif
        @endif
    </div>
@endsection
