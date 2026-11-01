@extends('layouts.app')

@section('title', 'Recetas de productos · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.recetas._header')
        @include('partials.mensajes')

        @include('admin.recetas._productos')
    </div>

    @include('admin.recetas._panel_receta')
    @include('admin.recetas._modal_editar')
@endsection

@push('scripts')
    <script type="module">
        $(document).ready(function () {
            // Receta de cada producto de la pagina: { id: { nombre, categoria, detalles: [...] } }
            const productos = @json($recetasPorProducto);

            @php($permisosReceta = ['crear' => $puedeCrear, 'editar' => $puedeEditar, 'baja' => $puedeBaja])
            const permisos = @json($permisosReceta);

            const rutaEditar = @json(route('dashboard.recetas.update', '__DETALLE__'));
            const rutaBaja = @json(route('dashboard.recetas.baja', '__DETALLE__'));

            // Producto cuyo panel debe abrirse: el que acaba de modificarse o
            // el que devolvió errores de validacion.
            @php($productoPanel = session('producto_abierto', old('idproducto')))
            const productoReabierto = @json($productoPanel);

            function cantidadTexto(cantidad) {
                return Number(cantidad).toLocaleString('es-BO', { maximumFractionDigits: 3 });
            }

            function botonSinPermiso(codigo, accion) {
                return $('<button>', { type: 'button', class: 'btn btn-sm btn-outline-secondary' })
                    .attr({
                        'data-bs-toggle': 'modal',
                        'data-bs-target': '#sinPermisoModal',
                        'data-mensaje': `No tienes permiso para ${accion}. Solicita el permiso <strong>${codigo}</strong>.`,
                    })
                    .text('Sin permiso');
            }

            function accionesDetalle(detalle) {
                const $acciones = $('<div>', { class: 'd-flex gap-1 flex-shrink-0' });

                if (permisos.editar) {
                    $acciones.append(
                        $('<button>', {
                            type: 'button',
                            class: 'btn btn-sm btn-outline-brand editar-detalle',
                        })
                            .attr({
                                'data-id': detalle.iddetalle,
                                'data-producto': detalle.idproducto,
                                'data-insumo': detalle.idinsumo,
                                'data-cantidad': detalle.cantidad,
                            })
                            .attr({ 'data-bs-toggle': 'modal', 'data-bs-target': '#editarRecetaModal' })
                            .text('Editar')
                    );
                } else {
                    $acciones.append(botonSinPermiso('341', 'modificar recetas'));
                }

                if (permisos.baja) {
                    $acciones.append(
                        $('<button>', {
                            type: 'button',
                            class: 'btn btn-sm btn-outline-danger quitar-detalle',
                        })
                            .attr({
                                'data-id': detalle.iddetalle,
                                'data-insumo': detalle.insumo ?? 'el insumo',
                                'data-bs-toggle': 'modal',
                                'data-bs-target': '#confirmarBajaModal',
                            })
                            .text('Quitar')
                    );
                } else {
                    $acciones.append(botonSinPermiso('345', 'quitar insumos de las recetas'));
                }

                return $acciones;
            }

            function pintarDetalles(detalles) {
                const $lista = $('#panel-receta-lista');

                $lista.empty();
                $('#panel-receta-total').text(detalles.length);
                $('#panel-receta-vacio').toggleClass('d-none', detalles.length > 0);
                $lista.toggleClass('d-none', detalles.length === 0);

                detalles.forEach((detalle) => {
                    const $fila = $('<li>', { class: 'list-group-item d-flex justify-content-between align-items-center gap-2' });
                    const $datos = $('<div>', { class: 'flex-grow-1' });

                    $datos.append(
                        $('<div>', { class: 'fw-semibold small' }).text(detalle.insumo ?? 'Insumo no disponible')
                    );
                    $datos.append(
                        $('<div>', { class: 'small text-muted' })
                            .text(`${cantidadTexto(detalle.cantidad)} ${detalle.unidad ?? ''}`.trim())
                    );

                    $fila.append($datos).append(accionesDetalle(detalle));
                    $lista.append($fila);
                });
            }

            function abrirPanel(idProducto) {
                const producto = productos[idProducto];

                if (!producto) {
                    return;
                }

                $('#receta-idproducto').val(String(idProducto));
                $('#panel-receta-titulo').text(producto.nombre);
                $('#panel-receta-subtitulo').text(producto.categoria ?? 'Sin categoria');
                pintarDetalles(producto.detalles);

                bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('panelReceta')).show();
            }

            $(document).on('click', '.abrir-panel-receta', function () {
                abrirPanel($(this).data('producto'));
            });

            $(document).on('click', '.editar-detalle', function () {
                $('#form-editar-receta').attr(
                    'action',
                    rutaEditar.replace('__DETALLE__', String($(this).attr('data-id')))
                );
                $('#editar-receta-producto').val(String($(this).attr('data-producto')));
                $('#editar-receta-insumo').val(String($(this).attr('data-insumo')));
                $('#editar-receta-cantidad').val(String($(this).attr('data-cantidad')));
            });

            $(document).on('click', '.quitar-detalle', function () {
                $('#form-quitar-receta').attr(
                    'action',
                    rutaBaja.replace('__DETALLE__', String($(this).attr('data-id')))
                );
                $('#confirmar-baja-texto').text(
                    `¿Seguro que quieres quitar "${$(this).attr('data-insumo')}" de la receta?`
                );
            });

            $('#sinPermisoModal').on('show.bs.modal', function (evento) {
                $('#sinPermisoMensaje').html($(evento.relatedTarget).data('mensaje'));
            });

            if (productoReabierto) {
                abrirPanel(productoReabierto);
            }
        });
    </script>
@endpush
