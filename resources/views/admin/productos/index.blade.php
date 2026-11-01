@extends('layouts.app')

@section('title', 'Catálogo de productos · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        @include('admin.productos._header')
        @include('partials.mensajes')

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                @include('admin.productos._tabla_productos')
            </div>
            <div class="col-12 col-lg-4">
                @include('admin.productos._tabla_categorias')
            </div>
        </div>
    </div>

    @include('admin.productos._modal_producto')
    @include('admin.productos._modal_categoria')

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
            const storageUrl = '{{ asset('storage') }}';

            // Modal editar producto
            const editarProductoModal = document.getElementById('editarProductoModal');
            if (editarProductoModal) {
                editarProductoModal.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;
                    const id = btn.dataset.id;
                    const nombre = btn.dataset.nombre ?? '';
                    const descripcion = btn.dataset.descripcion ?? '';
                    const categoriaId = btn.dataset.categoria ?? '';
                    const photo = btn.dataset.photo ?? '';

                    const form = document.getElementById('form-editar-producto');
                    const template = form.dataset.updateUrlTemplate;
                    form.action = template.replace('ID', encodeURIComponent(id));

                    document.getElementById('editarProductoNombre').value = nombre;
                    document.getElementById('editarProductoDescripcion').value = descripcion;

                    const categoriaSelect = document.getElementById('editarProductoCategoria');
                    const tieneOpcionActiva = Array.from(categoriaSelect.options).some(option => option.value === categoriaId);

                    if (tieneOpcionActiva) {
                        categoriaSelect.value = categoriaId;
                    } else {
                        // La categoría actual está inactiva: la mostramos como opción temporal
                        // para que el usuario la vea y elija una categoría activa.
                        const option = document.createElement('option');
                        option.value = categoriaId;
                        option.text = '{{ __('Categoría inactiva') }}';
                        option.selected = true;
                        option.disabled = true;
                        option.classList.add('text-muted');
                        categoriaSelect.prepend(option);
                    }

                    // Foto actual
                    const fotoActual = document.getElementById('fotoActual');
                    const fotoActualImg = document.getElementById('fotoActualImg');
                    const quitarPhoto = document.getElementById('quitarPhoto');

                    quitarPhoto.checked = false;

                    if (photo) {
                        fotoActualImg.src = `${storageUrl}/${photo}`;
                        fotoActual.classList.remove('d-none');
                        fotoActual.classList.add('d-flex');
                    } else {
                        fotoActual.classList.add('d-none');
                        fotoActual.classList.remove('d-flex');
                    }
                });

                editarProductoModal.addEventListener('hidden.bs.modal', () => {
                    // Limpia el input file para evitar conservar una selección anterior.
                    document.getElementById('editarProductoPhoto').value = '';

                    // Elimina la opción temporal de categoría inactiva si existe.
                    const categoriaSelect = document.getElementById('editarProductoCategoria');
                    const opcionInactiva = categoriaSelect.querySelector('option.disabled.text-muted');
                    if (opcionInactiva) {
                        opcionInactiva.remove();
                    }
                });
            }

            // Modal editar categoría
            const editarCategoriaModal = document.getElementById('editarCategoriaModal');
            if (editarCategoriaModal) {
                editarCategoriaModal.addEventListener('show.bs.modal', (event) => {
                    const btn = event.relatedTarget;
                    const id = btn.dataset.id;

                    const form = document.getElementById('form-editar-categoria');
                    const template = '{{ route('dashboard.productos.categoria.update', 'ID') }}';
                    form.action = template.replace('ID', encodeURIComponent(id));

                    document.getElementById('editarCategoriaNombre').value = btn.dataset.nombre ?? '';
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
