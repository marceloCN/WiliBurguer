@extends('layouts.app')

@section('title', 'Datos de usuarios · ' . $configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Datos de usuarios</h1>
                <p class="text-muted mb-0">Edita la información personal y de contacto.</p>
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

                @include('admin.usuarios._filtros', ['ruta' => route('dashboard.usuarios.datos')])

                <div class="card tarjeta-producto">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Empleado</th>
                                    <th>Usuario / correo</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($empleados as $empleado)
                                    @php
                                        $esMiCuenta = $empleado->id === auth()->user()->empleado->id;
                                        $tieneCuenta = (bool) $empleado->cuentaUsuario;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $empleado->nombre }} {{ $empleado->apellido }}</div>
                                        </td>
                                        <td>
                                            @if ($tieneCuenta)
                                                <div>{{ $empleado->cuentaUsuario->usuario }}</div>
                                                <div class="text-muted small">{{ $empleado->cuentaUsuario->correo }}</div>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
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
                                        <td class="text-end">
                                            @if ($tieneCuenta)
                                                @if ($esMiCuenta)
                                                    <span class="badge text-bg-light border">Tú</span>
                                                @else
                                                    @if ($permisosUsuario->contains('102'))
                                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#verUsuarioModal"
                                                                data-nombre="{{ $empleado->nombre }}"
                                                                data-apellido="{{ $empleado->apellido }}"
                                                                data-telefono="{{ $empleado->telefono }}"
                                                                data-usuario="{{ $empleado->cuentaUsuario->usuario }}"
                                                                data-correo="{{ $empleado->cuentaUsuario->correo }}"
                                                                data-rol="{{ $empleado->rol?->nombrerol ?? '—' }}"
                                                                data-sucursal="{{ $empleado->sucursal?->nombre ?? '—' }}"
                                                                data-estado="{{ $empleado->estado ? 'Activo' : 'Inactivo' }}"
                                                                data-bloqueado="{{ $empleado->cuentaUsuario->bloqueado ? 'Sí' : 'No' }}"
                                                                data-ultimo-acceso="{{ $empleado->cuentaUsuario->ultimoacceso?->format('d/m/Y H:i') ?? '—' }}"
                                                                data-fecha-registro="{{ $empleado->fecharegistro?->format('d/m/Y') ?? '—' }}">
                                                            Ver
                                                        </button>
                                                    @else
                                                        <button class="btn btn-sm btn-outline-info"
                                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Ver datos de usuario', '102')">
                                                            Ver
                                                        </button>
                                                    @endif

                                                    @php
                                                        $codigoEstado = $sucursalGerente ? '207' : '105';
                                                        $puedeEstado = $permisosUsuario->contains($codigoEstado);
                                                    @endphp
                                                    @if ($puedeEstado)
                                                        <form method="POST" action="{{ route('dashboard.usuarios.estado', $empleado->cuentaUsuario) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm {{ $empleado->estado ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                                {{ $empleado->estado ? 'Desactivar' : 'Activar' }}
                                                            </button>
                                                        </form>
                                                    @else
                                                        <button type="button" class="btn btn-sm {{ $empleado->estado ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Cambiar estado de cuenta', '{{ $codigoEstado }}')">
                                                            {{ $empleado->estado ? 'Desactivar' : 'Activar' }}
                                                        </button>
                                                    @endif

                                                    @if ($empleado->estado && $permisosUsuario->contains('101'))
                                                        <button class="btn btn-sm btn-outline-brand" data-bs-toggle="modal" data-bs-target="#editarDatosModal"
                                                                data-nombre="{{ $empleado->nombre }}" data-apellido="{{ $empleado->apellido }}"
                                                                data-telefono="{{ $empleado->telefono }}" data-usuario="{{ $empleado->cuentaUsuario->usuario }}"
                                                                data-correo="{{ $empleado->cuentaUsuario->correo }}"
                                                                data-url="{{ route('dashboard.usuarios.actualizar', $empleado->cuentaUsuario) }}">
                                                            Editar datos
                                                        </button>
                                                    @elseif ($empleado->estado)
                                                        <button class="btn btn-sm btn-outline-brand"
                                                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Editar datos de usuario', '101')">
                                                            Editar datos
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
                                        <td colspan="4" class="text-center py-4 text-muted">No hay empleados registrados.</td>
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

    @include('admin.usuarios._modal_datos')
    @include('admin.usuarios._modal_ver')
    @include('admin.usuarios._modal_crear')
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function() {
            $('#verUsuarioModal').on('show.bs.modal', function(event) {
                const $boton = $(event.relatedTarget);

                $('#verNombreCompleto').text($boton.data('nombre') + ' ' + $boton.data('apellido'));
                $('#verTelefono').text($boton.data('telefono') || '—');
                $('#verUsuario').text($boton.data('usuario'));
                $('#verCorreo').text($boton.data('correo') || '—');
                $('#verRol').text($boton.data('rol'));
                $('#verSucursal').text($boton.data('sucursal'));
                $('#verEstado').text($boton.data('estado'));
                $('#verBloqueado').text($boton.data('bloqueado'));
                $('#verUltimoAcceso').text($boton.data('ultimo-acceso'));
                $('#verFechaRegistro').text($boton.data('fecha-registro'));
            });
            
            $('#editarDatosModal').on('show.bs.modal', function(event) {
                const $boton = $(event.relatedTarget);
                const nombre = $boton.data('nombre');
                const apellido = $boton.data('apellido');

                $('#datosNombreEmpleado').text(nombre + ' ' + apellido);
                $('#datosUsuarioEmpleado').text($boton.data('usuario'));

                $('#datosNombre').val(nombre);
                $('#datosApellido').val(apellido);
                $('#datosTelefono').val($boton.data('telefono') || '');
                $('#datosUsuario').val($boton.data('usuario'));
                $('#datosCorreo').val($boton.data('correo') || '');

                $('#editarDatosForm').attr('action', $boton.data('url'));
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
