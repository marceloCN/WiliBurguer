@extends('layouts.app')

@section('title', 'Inventario · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.inventario._header')
        @include('partials.mensajes')

        <div class="row g-4">
            @include('admin.inventario._sucursales')

            <div class="{{ $esAdmin ? 'col-12 col-lg-8' : 'col-12' }}">
                @if (! $esAdmin)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge text-bg-brand">{{ $sucursalActual->nombre }}</span>
                        <span class="text-muted small">Inventario de tu sucursal</span>
                    </div>
                @endif

                @include('admin.inventario._stock')
                @include('admin.inventario._movimientos')
            </div>
        </div>
    </div>

    @include('admin.inventario._modal_movimiento')
    @include('admin.inventario._modal_minimo')
    @include('admin.inventario._modal_reporte')
    @include('admin.inventario._modal_detalle_movimiento')
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function () {
            // Modal editar cantidad mínima
            $('#editarMinimoModal').on('show.bs.modal', function (event) {
                const $btn = $(event.relatedTarget);
                const id = $btn.data('id');
                const insumo = $btn.data('insumo');
                const minimo = $btn.data('minimo');

                $('#form-editar-minimo').attr('action', '{{ route('dashboard.inventario.minimo', 'ID') }}'.replace('ID', id));
                $('#minimoInsumoNombre').text(insumo);
                $('#minimoCantidad').val(minimo !== '' && minimo !== null ? minimo : '');
            });

            // Modal detalle de movimiento
            $('#detalleMovimientoModal').on('show.bs.modal', function (event) {
                const $btn = $(event.relatedTarget);
                const tipo = $btn.data('tipo');
                const clasesTipo = {
                    'ENTRADA': 'text-bg-success',
                    'SALIDA': 'text-bg-danger',
                    'AJUSTE': 'text-bg-warning',
                };
                const clase = clasesTipo[tipo] ?? 'text-bg-dark';

                $('#detalle-fecha').text($btn.data('fecha'));
                $('#detalle-tipo').html(`<span class="badge ${clase}">${tipo}</span>`);
                $('#detalle-insumo').text($btn.data('insumo'));
                $('#detalle-unidad').text($btn.data('unidad'));
                $('#detalle-cantidad').text($btn.data('cantidad'));
                $('#detalle-anterior').text($btn.data('anterior'));
                $('#detalle-nuevo').text($btn.data('nuevo'));
                $('#detalle-empleado').text($btn.data('empleado'));
                $('#detalle-observacion').text($btn.data('observacion'));
            });

            // Modal reportes PDF: mostrar/ocultar fecha según tipo
            const $selectTipo = $('#reporteTipo');
            const $campoFecha = $('#reporteFechaDesde');

            function alternarFechaReporte() {
                const esMovimientos = $selectTipo.val() === 'movimientos';
                $campoFecha.toggle(esMovimientos);
                $campoFecha.find('input[name="fecha_desde"]').prop('required', esMovimientos);
            }

            $selectTipo.on('change', alternarFechaReporte);
            alternarFechaReporte();
        });
    </script>
@endpush
