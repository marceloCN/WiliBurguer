@extends('layouts.app')

@section('title', 'Insumos y unidades de medida · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.insumos._header')
        @include('partials.mensajes')

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                @include('admin.insumos._tabla_insumos')
            </div>
            <div class="col-12 col-lg-4">
                @include('admin.insumos._tabla_unidades')
            </div>
        </div>
    </div>

    @include('admin.insumos._modal_insumo')
    @include('admin.insumos._modal_unidad')
    @include('admin.insumos._modal_ayuda')

    <div class="modal fade" id="sinPermisoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Acción no permitida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0" id="sinPermisoMensaje"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function () {
            // Modal editar insumo
            $('#editarInsumoModal').on('show.bs.modal', function (event) {
                const $btn = $(event.relatedTarget);
                const id = $btn.data('id');
                const nombre = $btn.data('nombre');
                const descripcion = $btn.data('descripcion');
                const unidad = $btn.data('unidad');

                $('#form-editar-insumo').attr('action', '{{ route('dashboard.insumos.update', 'ID') }}'.replace('ID', id));
                $('#editarInsumoNombre').val(nombre);
                $('#editarInsumoDescripcion').val(descripcion ?? '');
                $('#editarInsumoUnidad').val(unidad);
            });

            // Modal editar unidad de medida
            $('#editarUnidadModal').on('show.bs.modal', function (event) {
                const $btn = $(event.relatedTarget);
                const id = $btn.data('id');
                const nombre = $btn.data('nombre');
                const abreviatura = $btn.data('abreviatura');
                const tipo = $btn.data('tipo');

                $('#form-editar-unidad').attr('action', '{{ route('dashboard.insumos.unidad.update', 'ID') }}'.replace('ID', id));
                $('#editarUnidadNombre').val(nombre);
                $('#editarUnidadAbreviatura').val(abreviatura);
                $('#editarUnidadTipo').val(tipo);
            });

            // Modal genérico de sin permiso
            $('#sinPermisoModal').on('show.bs.modal', function (event) {
                const mensaje = $(event.relatedTarget).data('mensaje');
                $('#sinPermisoMensaje').html(mensaje);
            });

            // Filtro automático de la tabla de insumos
            const $buscador = $('#busqueda-insumos');
            const $filas = $('#tabla-insumos tbody tr');

            $buscador.on('input', function () {
                const texto = $(this).val().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

                $filas.each(function () {
                    const filaTexto = $(this).text().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
                    $(this).toggle(filaTexto.includes(texto));
                });
            });
        });
    </script>
@endpush
