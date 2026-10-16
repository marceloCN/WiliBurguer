@extends('layouts.app')

@section('title', 'Seguridad de usuarios · ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Seguridad de usuarios</h1>
                <p class="text-muted mb-0">Restablece contraseñas y revisa el estado de bloqueo.</p>
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

                @include('admin.usuarios._filtros', ['ruta' => route('dashboard.usuarios.seguridad')])

                <div class="card tarjeta-producto">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Usuario</th>
                                    <th>Estado</th>
                                    <th>Último acceso</th>
                                    <th>Bloqueado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($empleados as $empleado)
                                    @php
                                        $esMiCuenta = $empleado->id === auth()->user()->empleado->id;
                                        $tieneCuenta = (bool) $empleado->cuentaUsuario;
                                        $puedeOperar = $tieneCuenta && ! $esMiCuenta && $empleado->estado;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $empleado->nombre }} {{ $empleado->apellido }}</div>
                                        </td>
                                        <td>{{ $empleado->cuentaUsuario?->usuario ?? '—' }}</td>
                                        <td>
                                            @if (! $tieneCuenta)
                                                <span class="badge text-bg-secondary">Sin cuenta</span>
                                            @elseif ($empleado->estado && ! $empleado->cuentaUsuario->bloqueado)
                                                <span class="badge text-bg-success">Activo</span>
                                            @elseif (! $empleado->estado)
                                                <span class="badge text-bg-danger">Inactivo</span>
                                            @else
                                                <span class="badge text-bg-warning">Bloqueado</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            {{ $empleado->cuentaUsuario?->ultimoacceso?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td>
                                            @if ($tieneCuenta && $empleado->cuentaUsuario->bloqueado)
                                                <span class="badge text-bg-warning">Sí</span>
                                            @else
                                                <span class="badge text-bg-success">No</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($tieneCuenta)
                                                @if ($esMiCuenta)
                                                    <span class="badge text-bg-light border">Tú</span>
                                                @else
                                                    @if ($permisosUsuario->contains('106'))
                                                        <button class="btn btn-sm btn-outline-brand" data-bs-toggle="modal" data-bs-target="#editarSeguridadModal"
                                                                data-nombre="{{ $empleado->nombre }}" data-apellido="{{ $empleado->apellido }}"
                                                                data-usuario="{{ $empleado->cuentaUsuario->usuario }}"
                                                                data-url="{{ route('dashboard.usuarios.password', $empleado->cuentaUsuario) }}"
                                                                @if (! $puedeOperar) disabled @endif>
                                                            Cambiar contraseña
                                                        </button>
                                                    @else
                                                        <button class="btn btn-sm btn-outline-brand"
                                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Cambiar contraseña de usuario', '106')">
                                                            Cambiar contraseña
                                                        </button>
                                                    @endif
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">No hay empleados registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($empleados->hasPages())
                        <div class="card-footer bg-light-subtle border-top py-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                                <span>
                                    Mostrando {{ $empleados->firstItem() }}–{{ $empleados->lastItem() }}
                                    de {{ $empleados->total() }} usuarios
                                </span>
                                <nav aria-label="Paginación de usuarios">
                                    {{ $empleados->onEachSide(1)->links() }}
                                </nav>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('admin.usuarios._modal_seguridad')
    @include('admin.usuarios._modal_crear')
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function() {
            $('#editarSeguridadModal').on('show.bs.modal', function(event) {
                const $boton = $(event.relatedTarget);
                const nombre = $boton.data('nombre');
                const apellido = $boton.data('apellido');

                $('#seguridadNombreEmpleado').text(nombre + ' ' + apellido);
                $('#seguridadUsuarioEmpleado').text($boton.data('usuario'));

                $('#editarSeguridadForm').attr('action', $boton.data('url'));
            });

            const nombresUsuarios = new Set(@json($usuariosExistentes));

            function slugificarUsuario(texto) {
                return texto.toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '.')
                    .replace(/^\.+|\.+$/g, '')
                    .replace(/\.{2,}/g, '.');
            }

            function sugerirUsuario(nombre, apellido) {
                const base = slugificarUsuario((nombre || '') + ' ' + (apellido || ''));
                if (!base) return '';
                if (!nombresUsuarios.has(base)) return base;

                let sufijo = 1;
                while (nombresUsuarios.has(base + sufijo)) sufijo++;
                return base + sufijo;
            }

            const $crearModal = $('#crearUsuarioModal');
            const $crearNombre = $crearModal.find('.crear-nombre');
            const $crearApellido = $crearModal.find('.crear-apellido');
            const $crearUsuario = $crearModal.find('.crear-usuario');
            let usuarioGenerado = false;

            function regenerarUsuario() {
                if (usuarioGenerado || !$crearUsuario.val()) {
                    $crearUsuario.val(sugerirUsuario($crearNombre.val(), $crearApellido.val()));
                    usuarioGenerado = true;
                }
            }

            $crearNombre.on('input', regenerarUsuario);
            $crearApellido.on('input', regenerarUsuario);
            $crearUsuario.on('input', function() {
                usuarioGenerado = false;
            });
            $crearModal.on('hidden.bs.modal', function() {
                usuarioGenerado = false;
                $crearUsuario.val('');
            });
        });
    </script>
@endpush
