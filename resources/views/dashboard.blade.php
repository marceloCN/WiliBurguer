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
                        @if ($rol->claverol === 'MESERO' || $rol->claverol === 'CAJERO' || $rol->claverol === 'GERENTE')
                            <a href="{{ route('dashboard.generar-pedidos.index') }}" class="btn btn-brand btn-lg mb-2 w-100 w-lg-auto">
                                🧾 Generar pedido
                            </a>
                        @endif
                        @if ($rol->claverol === 'CAJERO')
                            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-brand w-100 w-lg-auto">
                                💰 Ir a caja
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('partials.mensajes')

        @if (isset($permisos['inventario']) || isset($permisos['insumos']))
            <h2 class="h5 fw-bold mb-3">Gestión de inventario e insumos</h2>
            <div class="row g-3 mb-4">
                @if (isset($permisos['inventario']))
                    <div class="col-12 col-md-6">
                        <a href="{{ route('dashboard.inventario.index') }}" class="card tarjeta-producto h-100 text-decoration-none">
                            <div class="card-body d-flex align-items-center gap-4 p-4">
                                <span class="badge text-bg-brand p-3 rounded-3 fs-2">📦</span>
                                <div>
                                    <h3 class="h5 fw-bold mb-1 text-dark">Inventario por sucursal</h3>
                                    <p class="text-muted small mb-2">
                                        Revisa el stock actual, registra entradas, salidas y ajustes, y descarga reportes PDF.
                                    </p>
                                    <span class="btn btn-sm btn-brand">Ir a inventario</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
                @if (isset($permisos['insumos']))
                    <div class="col-12 col-md-6">
                        <a href="{{ route('dashboard.insumos.index') }}" class="card tarjeta-producto h-100 text-decoration-none">
                            <div class="card-body d-flex align-items-center gap-4 p-4">
                                <span class="badge text-bg-info p-3 rounded-3 fs-2">🧂</span>
                                <div>
                                    <h3 class="h5 fw-bold mb-1 text-dark">Catálogo de insumos</h3>
                                    <p class="text-muted small mb-2">
                                        Consulta los insumos disponibles en la empresa, sus unidades de medida y la guía de ayuda.
                                    </p>
                                    <span class="btn btn-sm btn-info text-white">Ver insumos</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>
        @endif

        @if ($sinSucursal)
            <div class="alert alert-warning shadow-sm d-flex align-items-center gap-3">
                <span class="fs-4">⏳</span>
                <div>
                    <strong>Sin sucursal asignada</strong><br>
                    Aun no tienes una sucursal asignada. Contacta al administrador para que te asigne una y puedas comenzar a operar.
                </div>
            </div>
        @else
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

            {{-- Modulos / Accesos rapidos --}}
            @if (! empty($permisos))
                <h2 class="h5 fw-bold mb-3">Modulos del sistema</h2>
                <div class="row g-3 mb-4">
                    @forelse ($permisos as $codigo => $modulo)
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
                    @empty
                        <div class="col-12">
                            <div class="alert alert-info shadow-sm mb-0">
                                Tu rol no tiene modulos asignados en este panel.
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Acciones rapidas por rol --}}
            <h2 class="h5 fw-bold mb-3">Acciones rapidas</h2>
            <div class="row g-3">
                @switch($rol->claverol)
                    @case('ADMINISTRADOR')
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-brand w-100 text-start py-3">
                                🏪 Gestionar sucursales
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.usuarios.dashboard') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                🧑‍💼 Gestionar empleados
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.configuracion.index') }}" class="btn btn-outline-warning w-100 text-start py-3">
                                ⚙️ Configurar sitio
                            </a>
                        </div>
                        @break

                    @case('GERENTE')
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-brand w-100 text-start py-3">
                                🏪 Ver mis sucursales
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.generar-pedidos.index') }}" class="btn btn-outline-success w-100 text-start py-3">
                                🧾 Generar pedido
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.usuarios.dashboard') }}" class="btn btn-outline-primary w-100 text-start py-3">
                                🧑‍💼 Ver empleados
                            </a>
                        </div>
                        @break

                    @case('CAJERO')
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-brand w-100 text-start py-3">
                                💰 Abrir / cerrar caja
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.generar-pedidos.index') }}" class="btn btn-outline-success w-100 text-start py-3">
                                🧾 Ver pedidos
                            </a>
                        </div>
                        @break

                    @case('MESERO')
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.generar-pedidos.index') }}" class="btn btn-brand w-100 text-start py-3">
                                🧾 Tomar pedido
                            </a>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <a href="{{ route('dashboard.sucursales.index') }}" class="btn btn-outline-brand w-100 text-start py-3">
                                🪑 Ver mesas
                            </a>
                        </div>
                        @break
                @endswitch
            </div>
        @endif
    </div>
@endsection
