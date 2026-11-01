@extends('layouts.app')

@section('title', 'Productos por sucursal · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.productos-sucursal._header')
        @include('partials.mensajes')

        <div class="row g-4">
            @include('admin.productos-sucursal._sucursales')

            <div class="{{ $conSelectorSucursal ? 'col-12 col-lg-8' : 'col-12' }}">
                @if (! $conSelectorSucursal)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge text-bg-brand">{{ $sucursalActual->nombre }}</span>
                        <span class="text-muted small">Productos de tu sucursal</span>
                    </div>
                @endif

                @include('admin.productos-sucursal._productos')
            </div>
        </div>
    </div>

    @include('admin.productos-sucursal._modal_producto')

    @if ($puedeVerHistorial)
        {{-- Modal con el historial de precios de un producto (permiso 320) --}}
        <div class="modal fade" id="historialSPModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Historial de precios</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body" id="historialSPCuerpo">
                        <div class="text-center text-muted py-4">Cargando…</div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
        document.addEventListener('DOMContentLoaded', () => {
            // Modal editar precio: se rellena con los datos de la fila que abrió el modal
            const editarPrecioModal = document.getElementById('editarPrecioModal');
            if (editarPrecioModal) {
                editarPrecioModal.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;
                    const id = btn.dataset.id;

                    const form = document.getElementById('form-editar-precio');
                    const template = form.dataset.precioUrlTemplate;
                    form.action = template.replace('ID', encodeURIComponent(id));

                    document.getElementById('editarPrecioProducto').textContent = btn.dataset.producto ?? '';
                    document.getElementById('editarPrecioSucursal').textContent = btn.dataset.sucursal ?? '';
                    document.getElementById('editarPrecioValor').value = btn.dataset.precio ?? '';
                });
            }

            // Modal detalle del producto
            const detalleProductoModal = document.getElementById('detalleProductoModal');
            if (detalleProductoModal) {
                detalleProductoModal.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;

                    document.getElementById('detalleProductoNombre').textContent = btn.dataset.nombre ?? '—';
                    document.getElementById('detalleProductoCategoria').textContent = btn.dataset.categoria ?? 'Sin categoría';
                    document.getElementById('detalleProductoDescripcion').textContent = btn.dataset.descripcion ?? 'Sin descripción.';
                    document.getElementById('detalleProductoPrecio').textContent = `Bs. ${btn.dataset.precio ?? '0.00'}`;

                    const disponibilidad = btn.dataset.disponibilidad ?? 'No disponible';
                    const badgeDisponibilidad = document.getElementById('detalleProductoDisponibilidad');
                    badgeDisponibilidad.textContent = disponibilidad;
                    badgeDisponibilidad.className = 'badge ' + (disponibilidad === 'Disponible' ? 'text-bg-success' : 'text-bg-secondary');

                    const foto = btn.dataset.photo ?? '';
                    const imgFoto = document.getElementById('detalleProductoFoto');
                    const sinFoto = document.getElementById('detalleProductoSinFoto');

                    if (foto) {
                        imgFoto.src = foto;
                        imgFoto.classList.remove('d-none');
                        sinFoto.classList.add('d-none');
                    } else {
                        imgFoto.classList.add('d-none');
                        imgFoto.src = '';
                        sinFoto.classList.remove('d-none');
                    }
                });
            }

            // Modal asignar producto: filtra productos por categoría elegida
            const asignarProductoModal = document.getElementById('asignarProductoModal');
            if (asignarProductoModal) {
                const form = asignarProductoModal.querySelector('form');
                const productosDisponibles = JSON.parse(form.dataset.productosDisponibles || '[]');
                const selectCategoria = document.getElementById('asignarCategoria');
                const selectProducto = document.getElementById('asignarProducto');

                selectCategoria.addEventListener('change', () => {
                    const categoriaId = parseInt(selectCategoria.value, 10);
                    const filtrados = productosDisponibles.filter(producto => producto.idcategoria === categoriaId);

                    selectProducto.innerHTML = '<option value="" disabled selected>Selecciona un producto…</option>';

                    if (filtrados.length === 0) {
                        selectProducto.disabled = true;
                    } else {
                        filtrados.forEach(producto => {
                            const option = document.createElement('option');
                            option.value = producto.id;
                            option.textContent = producto.nombre;
                            selectProducto.appendChild(option);
                        });
                        selectProducto.disabled = false;
                    }
                });

                asignarProductoModal.addEventListener('hidden.bs.modal', () => {
                    selectCategoria.value = '';
                    selectProducto.innerHTML = '<option value="" disabled selected>Primero elige una categoría…</option>';
                    selectProducto.disabled = true;
                    document.getElementById('asignarPrecio').value = '';
                });
            }

            // Modal genérico de sin permiso
            const sinPermisoModal = document.getElementById('sinPermisoModal');
            if (sinPermisoModal) {
                sinPermisoModal.addEventListener('show.bs.modal', (event) => {
                    document.getElementById('sinPermisoMensaje').innerHTML = event.relatedTarget.dataset.mensaje ?? '';
                });
            }

            @if ($puedeVerHistorial)
            // Modal de historial de precios: carga el historial del producto
            // clicado vía AJAX y lo inyecta en el cuerpo del modal.
            const historialSPCuerpo = document.getElementById('historialSPCuerpo');
            document.addEventListener('click', (evento) => {
                const btn = evento.target.closest('[data-historial-url]');
                if (! btn) return;

                historialSPCuerpo.innerHTML = '<div class="text-center text-muted py-4">Cargando…</div>';

                fetch(btn.dataset.historialUrl)
                    .then((respuesta) => {
                        if (! respuesta.ok) throw new Error('Error de red');
                        return respuesta.text();
                    })
                    .then((html) => {
                        historialSPCuerpo.innerHTML = html;
                    })
                    .catch(() => {
                        historialSPCuerpo.innerHTML =
                            '<div class="text-center text-danger py-4">No se pudo cargar el historial de precios.</div>';
                    });
            });
            @endif
        });
    </script>
@endpush
