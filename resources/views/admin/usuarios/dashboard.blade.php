@extends('layouts.app')

@section('title', 'Administración de usuarios · ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Administración de usuarios</h1>
                <p class="text-muted mb-0">Dashboard general de empleados y cuentas.</p>
            </div>
            @php
                $puedeCrear = $permisosUsuario->contains('100')
                    && (! $sucursalGerente || $permisosUsuario->contains('206'));

                $codigosCrear = match (true) {
                    $sucursalGerente && ! $permisosUsuario->contains('100') && ! $permisosUsuario->contains('206') => '100, 206',
                    $sucursalGerente && ! $permisosUsuario->contains('206') => '206',
                    default => '100',
                };
            @endphp
            @if ($puedeCrear)
                <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                    + Nuevo usuario
                </button>
            @else
                <button class="btn btn-brand"
                    onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Crear nuevo usuario', '{{ $codigosCrear }}')">
                    + Nuevo usuario
                </button>
            @endif
        </div>

        @include('partials.mensajes')

        <div class="row g-4">
            <div class="col-12 col-lg-3">
                @include('admin.usuarios._aside')
            </div>

            <div class="col-12 col-lg-9">
                @if ($sucursalGerente)
                    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                        <span>🗺️</span>
                        <span>
                            Estás gestionando la sucursal <strong>{{ $sucursalGerente->nombre }}</strong>.
                            Solo puedes crear y administrar usuarios <strong>cajero o mesero</strong> de esta sucursal.
                        </span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-6 col-xl-3">
                        <div class="card tarjeta-producto h-100">
                            <div class="card-body text-center">
                                <div class="display-5 fw-bold text-brand">{{ $total }}</div>
                                <div class="text-muted small">Total empleados</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card tarjeta-producto h-100 border-start border-success border-4">
                            <div class="card-body text-center">
                                <div class="display-5 fw-bold text-success">{{ $activos }}</div>
                                <div class="text-muted small">Activos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card tarjeta-producto h-100 border-start border-danger border-4">
                            <div class="card-body text-center">
                                <div class="display-5 fw-bold text-danger">{{ $inactivos }}</div>
                                <div class="text-muted small">Inactivos</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card tarjeta-producto h-100 border-start border-warning border-4">
                            <div class="card-body text-center">
                                <div class="display-5 fw-bold text-warning">{{ $bloqueados }}</div>
                                <div class="text-muted small">Cuentas bloqueadas</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card tarjeta-producto">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 fw-bold">Distribución de estados</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-6">
                                <canvas id="estadosChart" height="250"></canvas>
                            </div>
                            <div class="col-12 col-md-6">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><span class="badge bg-success">●</span> Activos</span>
                                        <strong>{{ $activos }}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><span class="badge bg-danger">●</span> Inactivos</span>
                                        <strong>{{ $inactivos }}</strong>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><span class="badge bg-warning">●</span> Bloqueados</span>
                                        <strong>{{ $bloqueados }}</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.usuarios._modal_crear')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module">
        $(document).ready(function() {
            const ctx = document.getElementById('estadosChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Activos', 'Inactivos', 'Bloqueados'],
                    datasets: [{
                        data: [{{ $activos }}, {{ $inactivos }}, {{ $bloqueados }}],
                        backgroundColor: ['#198754', '#dc3545', '#ffc107'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
@endpush
