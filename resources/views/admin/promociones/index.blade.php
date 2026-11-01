@extends('layouts.app')

@section('title', 'Promociones · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.promociones._header')
        @include('partials.mensajes')

        <div class="row g-4">
            @include('admin.promociones._sucursales')

            <div class="{{ $conSelectorSucursal ? 'col-12 col-lg-8' : 'col-12' }}">
                @if (! $conSelectorSucursal)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge text-bg-brand">{{ $sucursalActual->nombre }}</span>
                        <span class="text-muted small">Promociones de tu sucursal</span>
                    </div>
                @endif

                @include('admin.promociones._tabla_promociones')
            </div>
        </div>
    </div>

    @include('admin.promociones._modal_promocion')
    @include('admin.promociones._modal_detalle')
    @include('admin.promociones._modal_reporte')

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
            const storageUrl = '{{ asset('storage') }}'.replace(/\/$/, '');

            function urlFoto(photo) {
                if (! photo) return '';
                if (photo.startsWith('http')) return photo;
                return `${storageUrl}/${photo.replace(/^\//, '')}`;
            }
            const modal = document.getElementById('promocionModal');

            const form = document.getElementById('form-promocion');
            const titulo = document.getElementById('promocionTitulo');
            const submit = document.getElementById('promocionSubmit');
            const bloqueSucursal = document.getElementById('bloqueSucursal');

            const inputNombre = document.getElementById('promocionNombre');
            const inputTipo = document.getElementById('promocionTipo');
            const inputValor = document.getElementById('promocionValor');
            const inputInicio = document.getElementById('promocionInicio');
            const inputFin = document.getElementById('promocionFin');
            const inputPhoto = document.getElementById('promocionPhoto');

            const fotoActual = document.getElementById('fotoActual');
            const fotoActualImg = document.getElementById('fotoActualImg');
            const fotoPreview = document.getElementById('fotoPreview');
            const fotoPreviewImg = document.getElementById('fotoPreviewImg');
            const quitarPhoto = document.getElementById('quitarPhoto');

            const checkboxesDias = document.querySelectorAll('.promocion-dias');
            const checkboxesProducto = document.querySelectorAll('.promocion-producto');
            const inputsCantidad = document.querySelectorAll('.promocion-cantidad');

            function resetForm() {
                inputNombre.value = '';
                inputTipo.value = 'PORCENTAJE';
                inputValor.value = '';
                inputInicio.value = '';
                inputFin.value = '';
                inputPhoto.value = '';
                quitarPhoto.checked = false;

                fotoActual.classList.add('d-none');
                fotoActual.classList.remove('d-flex');
                fotoActualImg.src = '';
                fotoPreview.classList.add('d-none');
                fotoPreview.classList.remove('d-flex');
                fotoPreviewImg.src = '';

                checkboxesDias.forEach(checkbox => checkbox.checked = false);
                checkboxesProducto.forEach(checkbox => checkbox.checked = false);
                inputsCantidad.forEach(input => {
                    input.value = 1;
                    input.disabled = true;
                });
            }

            function marcarProducto(idSucursalProducto, checked, cantidad = 1) {
                document.querySelectorAll(`.promocion-producto[value="${idSucursalProducto}"]`)
                    .forEach(checkbox => checkbox.checked = checked);

                document.querySelectorAll(`.promocion-cantidad[data-id="${idSucursalProducto}"]`)
                    .forEach(input => {
                        input.disabled = ! checked;
                        input.value = checked ? (input.value || cantidad) : 1;
                    });
            }

            if (modal) {
                modal.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;
                    const editando = btn.dataset.modo === 'editar';

                    form.action = editando
                        ? form.dataset.updateUrlTemplate.replace('ID', encodeURIComponent(btn.dataset.id))
                        : '{{ route('dashboard.promociones.store') }}';

                    titulo.textContent = editando ? 'Editar promoción' : 'Nueva promoción';
                    submit.textContent = editando ? 'Actualizar promoción' : 'Publicar promoción';
                    bloqueSucursal.classList.toggle('d-none', editando);

                    inputNombre.value = btn.dataset.nombre ?? '';
                    inputTipo.value = btn.dataset.tipo ?? 'PORCENTAJE';
                    inputValor.value = btn.dataset.valor ?? '';
                    inputInicio.value = btn.dataset.inicio ?? '';
                    inputFin.value = btn.dataset.fin ?? '';
                    inputPhoto.value = '';
                    quitarPhoto.checked = false;

                    // Foto actual
                    const photo = btn.dataset.photo ?? '';
                    if (photo) {
                        fotoActualImg.src = urlFoto(photo);
                        fotoActual.classList.remove('d-none');
                        fotoActual.classList.add('d-flex');
                    } else {
                        fotoActual.classList.add('d-none');
                        fotoActual.classList.remove('d-flex');
                        fotoActualImg.src = '';
                    }

                    fotoPreview.classList.add('d-none');
                    fotoPreview.classList.remove('d-flex');
                    fotoPreviewImg.src = '';

                    // Días
                    const dias = JSON.parse(btn.dataset.dias || '[]');
                    checkboxesDias.forEach(checkbox => checkbox.checked = dias.includes(parseInt(checkbox.value, 10)));

                    // Productos
                    const productos = JSON.parse(btn.dataset.productos || '{}');
                    checkboxesProducto.forEach(checkbox => {
                        const id = parseInt(checkbox.value, 10);
                        const cantidad = productos[id] ?? 1;
                        marcarProducto(id, id in productos, cantidad);
                    });
                });

                modal.addEventListener('hidden.bs.modal', () => {
                    resetForm();
                });
            }

            // Preview de la foto seleccionada
            if (inputPhoto) {
                inputPhoto.addEventListener('change', () => {
                    const file = inputPhoto.files[0];

                    if (file) {
                        fotoPreviewImg.src = URL.createObjectURL(file);
                        fotoPreview.classList.remove('d-none');
                        fotoPreview.classList.add('d-flex');
                    } else {
                        fotoPreview.classList.add('d-none');
                        fotoPreview.classList.remove('d-flex');
                        fotoPreviewImg.src = '';
                    }
                });
            }

            // Sincronizar checkboxes de producto (desktop y móvil) y habilitar cantidad
            checkboxesProducto.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    const id = checkbox.value;
                    const checked = checkbox.checked;

                    document.querySelectorAll(`.promocion-producto[value="${id}"]`)
                        .forEach(hermano => hermano.checked = checked);

                    document.querySelectorAll(`.promocion-cantidad[data-id="${id}"]`)
                        .forEach(input => {
                            input.disabled = ! checked;
                            if (checked && parseInt(input.value, 10) < 1) {
                                input.value = 1;
                            }
                        });
                });
            });

            // Modal de detalle
            const detalleModal = document.getElementById('detallePromocionModal');
            if (detalleModal) {
                detalleModal.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;

                    document.getElementById('detallePromocionNombre').textContent = btn.dataset.nombre ?? '—';
                    document.getElementById('detallePromocionDescuento').textContent = btn.dataset.descuento ?? '';

                    const estado = btn.dataset.estado ?? 'Inactiva';
                    const badgeEstado = document.getElementById('detallePromocionEstado');
                    badgeEstado.textContent = estado;
                    badgeEstado.className = 'badge ' + (estado === 'Activa' ? 'text-bg-success' : 'text-bg-secondary');

                    document.getElementById('detallePromocionVigencia').textContent = btn.dataset.vigencia ?? '';

                    const contenedorDias = document.getElementById('detallePromocionDias');
                    contenedorDias.innerHTML = '';
                    const dias = (btn.dataset.dias ?? '').split(', ').filter(dia => dia !== '');
                    if (dias.length === 0) {
                        contenedorDias.innerHTML = '<span class="text-muted small">Sin días configurados.</span>';
                    } else {
                        dias.forEach(dia => {
                            const span = document.createElement('span');
                            span.className = 'badge text-bg-light border';
                            span.textContent = dia;
                            contenedorDias.appendChild(span);
                        });
                    }

                    const listaProductos = document.getElementById('detallePromocionProductos');
                    listaProductos.innerHTML = '';
                    const productos = (btn.dataset.productos ?? '').split(', ').filter(producto => producto !== '');
                    if (productos.length === 0) {
                        listaProductos.innerHTML = '<li class="list-group-item text-muted small">Sin productos.</li>';
                    } else {
                        productos.forEach(producto => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item small';
                            li.textContent = producto;
                            listaProductos.appendChild(li);
                        });
                    }

                    const foto = btn.dataset.photo ?? '';
                    const imgFoto = document.getElementById('detallePromocionFoto');
                    const sinFoto = document.getElementById('detallePromocionSinFoto');
                    if (foto) {
                        imgFoto.src = urlFoto(foto);
                        imgFoto.classList.remove('d-none');
                        sinFoto.classList.add('d-none');
                    } else {
                        imgFoto.classList.add('d-none');
                        imgFoto.src = '';
                        sinFoto.classList.remove('d-none');
                    }

                    document.getElementById('detallePromocionAplicaHoy').classList.toggle('d-none', btn.dataset.aplicaHoy !== '1');
                });
            }

            // Modal genérico de sin permiso
            const sinPermisoModal = document.getElementById('sinPermisoModal');
            if (sinPermisoModal) {
                sinPermisoModal.addEventListener('show.bs.modal', (event) => {
                    document.getElementById('sinPermisoMensaje').innerHTML = event.relatedTarget.dataset.mensaje ?? '';
                });
            }
        });
    </script>
@endpush
