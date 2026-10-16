@extends('layouts.app')

@section('title', 'Bitácora de acciones · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Bitácora de acciones</h1>
                <p class="text-muted mb-0">Audita las acciones ejecutadas por los usuarios del sistema (permiso 151).</p>
            </div>
        </div>

        <div class="card tarjeta-producto mb-3">
            <div class="card-body">
                @include('admin.bitacora-acciones._filtros')
            </div>
        </div>

        <div class="card tarjeta-producto">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Empleado</th>
                            <th>Permiso efectuado</th>
                            <th class="text-center">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($acciones as $accion)
                            <tr>
                                <td class="small text-nowrap">{{ $accion->fechahora->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @if ($accion->empleado)
                                        <div class="fw-semibold">{{ $accion->empleado->nombre }} {{ $accion->empleado->apellido }}</div>
                                        <div class="text-muted small">{{ $accion->empleado->cuentaUsuario?->usuario }}
                                            <span class="badge text-bg-light border ms-1">{{ $accion->empleado->rol?->nombrerol }}</span>
                                        </div>
                                    @else
                                        <span class="text-muted">Empleado no registrado</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $accion->permiso?->nombre ?? '—' }}</div>
                                    <div class="text-muted small">Código: {{ $accion->permiso?->codigo ?? '—' }}</div>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detalleAccionModal"
                                            data-fecha="{{ $accion->fechahora->format('d/m/Y H:i:s') }}"
                                            data-empleado="{{ $accion->empleado ? $accion->empleado->nombre.' '.$accion->empleado->apellido : 'Empleado no registrado' }}"
                                            data-permiso="{{ $accion->permiso?->nombre ?? '—' }}"
                                            data-codigo="{{ $accion->permiso?->codigo ?? '—' }}"
                                            data-accion="{{ $accion->accion }}"
                                            data-tabla="{{ $accion->tablaafectada ?? '—' }}"
                                            data-registro="{{ $accion->idregistroafectado ?? '—' }}"
                                            data-equipo="{{ $accion->equipo ?? '—' }}"
                                            data-ip="{{ $accion->ip ?? '—' }}"
                                            data-resultado="{{ $accion->resultado }}">
                                        Ver información
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No hay acciones registradas con los filtros seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($acciones->hasPages())
                <div class="card-footer bg-light-subtle border-top py-3">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                        <span>
                            Mostrando {{ $acciones->firstItem() }}–{{ $acciones->lastItem() }}
                            de {{ $acciones->total() }} acciones
                        </span>
                        <nav aria-label="Paginación de acciones">
                            {{ $acciones->onEachSide(1)->links() }}
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @include('admin.bitacora-acciones._modal_detalle')
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
            $('#btn-pdf-acciones').on('click', function () {
                const $form = $('#form-filtros-acciones');
                const actionOriginal = $form.attr('action');
                $form.attr('action', '{{ route('dashboard.bitacora-acciones.pdf') }}');
                $form.submit();
                $form.attr('action', actionOriginal);
            });

            // Llenar modal de detalle de acción
            $('#detalleAccionModal').on('show.bs.modal', function (event) {
                const $btn = $(event.relatedTarget);

                $('#modal-accion-fecha').text($btn.data('fecha'));
                $('#modal-accion-empleado').text($btn.data('empleado'));
                $('#modal-accion-permiso').text($btn.data('permiso'));
                $('#modal-accion-codigo').text($btn.data('codigo'));
                $('#modal-accion-tabla').text($btn.data('tabla'));
                $('#modal-accion-registro').text($btn.data('registro'));
                $('#modal-accion-equipo').text($btn.data('equipo'));
                $('#modal-accion-ip').text($btn.data('ip'));
                $('#modal-accion-accion').html(badgeAccion($btn.data('accion')));
                $('#modal-accion-resultado').html(badgeResultado($btn.data('resultado')));
            });

            function badgeAccion(accion) {
                const clases = {
                    'CREATE': 'text-bg-success',
                    'UPDATE': 'text-bg-primary',
                    'DELETE': 'text-bg-danger',
                };
                const clase = clases[accion] ?? 'text-bg-dark';
                return `<span class="badge ${clase}">${accion}</span>`;
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
