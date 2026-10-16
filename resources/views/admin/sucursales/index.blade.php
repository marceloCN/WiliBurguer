@extends('layouts.app')

@section('title', 'Sucursales · ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @if (! empty($sucursalGerenteInactiva))
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card tarjeta-producto border-warning-subtle">
                        <div class="card-body text-center py-5">
                            <div class="display-5 mb-2">⛔</div>
                            <h1 class="h5 fw-bold mb-2">Sucursal desactivada</h1>
                            <p class="text-muted mb-4">
                                Tu sucursal está desactivada en el sistema.
                                Contacta al administrador para que la reactive.
                            </p>
                            <a href="{{ route('dashboard') }}" class="btn btn-brand">Ir al dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        @else
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Sucursales</h1>
                <p class="text-muted mb-0">Administra los datos, gerentes, horarios y equipos de cada sucursal.</p>
            </div>
            @if ($permisosUsuario->contains('200'))
                <a href="{{ route('dashboard.sucursales.crear') }}" class="btn btn-brand">
                    + Nueva sucursal
                </a>
            @else
                <button type="button" class="btn btn-brand"
                    onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Crear nueva sucursal', '200')">
                    + Nueva sucursal
                </button>
            @endif
        </div>

        @include('partials.mensajes')

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card tarjeta-producto h-100">
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold text-brand">{{ $totalSucursales }}</div>
                        <div class="text-muted small">Total sucursales</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card tarjeta-producto h-100 border-start border-primary border-4">
                    <div class="card-body text-center">
                        <div class="display-5 fw-bold text-primary">{{ $totalEmpleados }}</div>
                        <div class="text-muted small">Empleados asignados</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($sucursales->isEmpty())
            <div class="alert alert-light">No tienes sucursales disponibles.</div>
        @else
            <div class="row g-3">
                @foreach ($sucursales as $sucursal)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card tarjeta-producto h-100">
                            <div id="mapa-sucursal-{{ $sucursal->id }}" class="sucursal-mapa"
                                data-lat="{{ number_format((float) $sucursal->latitud, 6, '.', '') }}"
                                data-lng="{{ number_format((float) $sucursal->longitud, 6, '.', '') }}"></div>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                    <h2 class="h5 fw-bold mb-0">{{ $sucursal->nombre }}</h2>
                                    <span class="badge rounded-pill {{ $sucursal->estado ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $sucursal->estado ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </div>
                                <p class="small text-muted mb-1">📍 {{ $sucursal->direccion }}</p>
                                <p class="small text-muted mb-1">📞 {{ $sucursal->telefono }}</p>
                                <p class="small text-muted mb-3">
                                    🌐 {{ number_format((float) $sucursal->latitud, 6) }},
                                    {{ number_format((float) $sucursal->longitud, 6) }}
                                </p>
                                <p class="small text-muted mb-3">
                                    👤 Gerente:
                                    @if ($sucursal->gerente)
                                        {{ $sucursal->gerente->nombre }} {{ $sucursal->gerente->apellido }}
                                    @else
                                        No asignado
                                    @endif

                                </p>
                                @php
                                    $prioridadRol = ['GERENTE' => 1, 'CAJERO' => 2, 'MESERO' => 3];
                                    $empleadosJson = $sucursal->empleados
                                        ->filter(fn($e) => $e->estado)
                                        ->sortBy(fn($e) => $prioridadRol[$e->rol?->claverol] ?? 99)
                                        ->map(
                                            fn($e) => [
                                                'nombre_completo' => trim($e->nombre . ' ' . $e->apellido),
                                                'rol' => $e->rol?->nombrerol ?? '—',
                                            ],
                                        )
                                        ->values();
                                @endphp

                                <div class="mt-auto d-flex flex-wrap gap-2">
                                    @if ($permisosUsuario->intersect(['201', '222', '212', '402'])->isNotEmpty())
                                        <a href="{{ route('dashboard.sucursales.editar', $sucursal) }}"
                                            class="btn btn-brand btn-sm">
                                            Editar sucursal
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-brand btn-sm"
                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Editar sucursal', '201, 222, 212, 402')">
                                            Editar sucursal
                                        </button>
                                    @endif

                                    @if ($permisosUsuario->contains('208'))
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#modalEmpleadosSucursal" data-sucursal="{{ $sucursal->nombre }}"
                                            data-empleados='@json($empleadosJson)'>
                                            👥 Ver empleados
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Listar empleados de sucursal ', '208')">
                                            👥 Ver empleados
                                        </button>
                                    @endif

                                    @if ($permisosUsuario->contains('203'))
                                        <a href="{{ route('dashboard.sucursales.pdf', $sucursal) }}"
                                            class="btn btn-outline-danger btn-sm">
                                            📄 Mostrar info
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Mostrar informe de datos de sucursal', '203')">
                                            📄 Mostrar info
                                        </button>
                                    @endif

                                    @if ($permisosUsuario->contains('205'))
                                        @if ($esAdmin || ($esGerente && $sucursal->estado))
                                            <button type="button"
                                                class="btn {{ $sucursal->estado ? 'btn-brand' : 'btn-success' }} btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalBajaSucursal"
                                                data-accion="{{ route('dashboard.sucursales.estado', $sucursal) }}"
                                                data-nombre="{{ $sucursal->nombre }}"
                                                data-estado="{{ $sucursal->estado ? '1' : '0' }}">
                                                {{ $sucursal->estado ? 'Baja Sucursal' : 'Reactivar Sucursal' }}
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-brand btn-sm"
                                                onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Dar de baja a sucursal ', '205')">
                                                Baja Sucursal
                                            </button>
                                        @endif
                                    @else
                                        <button type="button" class="btn btn-brand btn-sm"
                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Dar de baja a sucursal ', '205')">
                                            Baja Sucursal
                                        </button>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        @endif
    </div>

    @include('admin.sucursales._modal_empleados')
    @include('admin.sucursales._modal_baja_sucursal')
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .sucursal-mapa {
            height: 170px;
            border-radius: 1rem 1rem 0 0;
            background: #eee;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module">
        $(document).ready(function() {
            @if (! empty($sucursalGerenteInactiva))
                const modalGerenteInactiva = new window.bootstrap.Modal(
                    document.getElementById('modalSucursalGerenteInactiva')
                );
                modalGerenteInactiva.show();
            @else
            // Inicializar mapas
            $('[id^="mapa-sucursal-"]').each(function() {
                const $contenedor = $(this);
                const lat = parseFloat($contenedor.data('lat'));
                const lng = parseFloat($contenedor.data('lng'));
                const mapa = L.map($contenedor.attr('id'), {
                    scrollWheelZoom: false
                }).setView([lat, lng], 15);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapa);

                L.marker([lat, lng]).addTo(mapa);
            });

            // Llenar modal de empleados
            $('#modalEmpleadosSucursal').on('show.bs.modal', function(event) {
                const $btn = $(event.relatedTarget);
                const nombreSucursal = $btn.data('sucursal');
                const empleados = $btn.data('empleados') || [];

                $('#modalEmpleadosSucursalLabel').text('Empleados de ' + escapeHtml(nombreSucursal));
                $('#modal-empleados-subtitulo').text(empleados.length + ' empleado(s) activo(s).');

                const $tbody = $('#modal-empleados-body');
                $tbody.empty();

                if (empleados.length === 0) {
                    $('#modal-empleados-vacio').removeClass('d-none');
                } else {
                    $('#modal-empleados-vacio').addClass('d-none');

                    empleados.forEach(function(empleado, index) {
                        $tbody.append(`
                            <tr>
                                <td>${index + 1}</td>
                                <td class="fw-semibold">${escapeHtml(empleado.nombre_completo)}</td>
                                <td>${empleado.rol ? escapeHtml(empleado.rol) : '—'}</td>
                            </tr>
                        `);
                    });
                }
            });

            // Modal de baja / reactivación de sucursal
            $('#modalBajaSucursal').on('show.bs.modal', function(event) {
                const $btn = $(event.relatedTarget);
                if (!$btn.length) return;

                const nombre = $btn.data('nombre') || '';
                const accion = $btn.data('accion') || '';
                const estado = String($btn.data('estado') ?? '1');
                const esBaja = estado === '1';

                $('#form-baja-sucursal').attr('action', accion);
                $('#advertencia-baja-sucursal').toggleClass('d-none', !esBaja);
                $('#confirmar-baja-sucursal-btn')
                    .toggleClass('btn-danger', esBaja)
                    .toggleClass('btn-success', !esBaja)
                    .text(esBaja ? '🗑️ Confirmar baja' : '✓ Confirmar reactivación');

                $('#texto-baja-sucursal').text(esBaja
                    ? 'Vas a dar de baja la sucursal "' + nombre + '".'
                    : 'Vas a reactivar la sucursal "' + nombre + '".');
            });
            @endif

            function escapeHtml(texto) {
                if (texto == null) return '';
                return String(texto)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
        });
    </script>
@endpush
