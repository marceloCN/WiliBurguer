@extends('layouts.app')

@section('title', 'Bitácora de accesos · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Bitácora de accesos</h1>
                <p class="text-muted mb-0">Audita los inicios y cierres de sesión de todos los usuarios.</p>
            </div>
        </div>

        <div class="card tarjeta-producto mb-3">
            <div class="card-body">
                @include('admin.bitacora-accesos._filtros')
            </div>
        </div>

        <div class="card tarjeta-producto">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Empleado</th>
                            <th>Tipo de acceso</th>
                            <th class="text-center">Ver detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($accesos as $acceso)
                            <tr>
                                <td class="small text-nowrap">{{ $acceso->fechahora->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @if ($acceso->empleado)
                                        <div class="fw-semibold">{{ $acceso->empleado->nombre }} {{ $acceso->empleado->apellido }}</div>
                                        <div class="text-muted small">{{ $acceso->empleado->cuentaUsuario?->usuario }}
                                            <span class="badge text-bg-light border ms-1">{{ $acceso->empleado->rol?->nombrerol }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">Empleado no registrado</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($acceso->tipoacceso === 'LOGIN')
                                        <span class="badge text-bg-primary">LOGIN</span>
                                    @elseif ($acceso->tipoacceso === 'LOGOUT')
                                        <span class="badge text-bg-secondary">LOGOUT</span>
                                    @else
                                        <span class="badge text-bg-dark">{{ $acceso->tipoacceso }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detalleAccesoModal"
                                            data-fecha="{{ $acceso->fechahora->format('d/m/Y H:i:s') }}"
                                            data-empleado="{{ $acceso->empleado ? $acceso->empleado->nombre.' '.$acceso->empleado->apellido : 'Empleado no registrado' }}"
                                            data-tipo="{{ $acceso->tipoacceso }}"
                                            data-resultado="{{ $acceso->resultado }}"
                                            data-equipo="{{ $acceso->equipo ?? '—' }}"
                                            data-ip="{{ $acceso->ip ?? '—' }}"
                                            data-detalle="{{ $acceso->detalle ?? '—' }}">
                                        Ver información
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No hay accesos registrados con los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($accesos->hasPages())
                <div class="card-footer bg-light-subtle border-top py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                        <span>
                            Mostrando {{ $accesos->firstItem() }}–{{ $accesos->lastItem() }}
                            de {{ $accesos->total() }} accesos
                        </span>
                        <nav aria-label="Paginación de accesos">
                            {{ $accesos->onEachSide(1)->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('admin.bitacora-accesos._modal_detalle')
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function () {
            const catalogoUsuarios = @json($catalogoUsuarios);
            const $selectSucursal = $('#idsucursal');
            const $selectUsuario = $('#idempleado');

            function rellenarUsuarios() {
                const sucursal = $selectSucursal.val();
                const seleccionPrevia = $selectUsuario.val();
                const disponibles = sucursal
                    ? catalogoUsuarios.filter(u => String(u.idsucursal) === sucursal)
                    : catalogoUsuarios;

                $selectUsuario.empty();
                $selectUsuario.append(`<option value="">${sucursal ? 'Todos los usuarios de la sucursal' : 'Todos'}</option>`);

                disponibles.forEach(u => {
                    $selectUsuario.append(`<option value="${u.id}">${u.label}</option>`);
                });

                if (seleccionPrevia && disponibles.some(u => String(u.id) === seleccionPrevia)) {
                    $selectUsuario.val(seleccionPrevia);
                }
            }

            $selectSucursal.on('change', rellenarUsuarios);

            // Generar PDF conservando los filtros actuales
            $('#btn-pdf-accesos').on('click', function () {
                const $form = $('#form-filtros-accesos');
                const actionOriginal = $form.attr('action');
                $form.attr('action', '{{ route('dashboard.bitacora-accesos.pdf') }}');
                $form.submit();
                $form.attr('action', actionOriginal);
            });

            // Llenar modal de detalle de acceso
            $('#detalleAccesoModal').on('show.bs.modal', function (event) {
                const $btn = $(event.relatedTarget);

                $('#modal-acceso-fecha').text($btn.data('fecha'));
                $('#modal-acceso-empleado').text($btn.data('empleado'));
                $('#modal-acceso-equipo').text($btn.data('equipo'));
                $('#modal-acceso-ip').text($btn.data('ip'));
                $('#modal-acceso-detalle').text($btn.data('detalle'));
                $('#modal-acceso-tipo').html(badgeTipo($btn.data('tipo')));
                $('#modal-acceso-resultado').html(badgeResultado($btn.data('resultado')));
            });

            function badgeTipo(tipo) {
                const clases = {
                    'LOGIN': 'text-bg-primary',
                    'LOGOUT': 'text-bg-secondary',
                };
                const clase = clases[tipo] ?? 'text-bg-dark';
                return `<span class="badge ${clase}">${tipo}</span>`;
            }

            function badgeResultado(resultado) {
                const clases = {
                    'EXITOSO': 'text-bg-success',
                    'FALLIDO': 'text-bg-danger',
                };
                const clase = clases[resultado] ?? 'text-bg-warning';
                return `<span class="badge ${clase}">${resultado}</span>`;
            }
        });
    </script>
@endpush
