<div class="modal fade" id="nuevoProductoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('dashboard.productos.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Nuevo producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" maxlength="100" required
                                   value="{{ old('nombre') }}" placeholder="Ej.: FULL, DOGOR, Salchipapas">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción *</label>
                            <textarea name="descripcion" rows="3" class="form-control" maxlength="200" required
                                      placeholder="Breve descripción del producto…">{{ old('descripcion') }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Categoría *</label>
                            <select name="idcategoria" class="form-select" required>
                                <option value="" disabled selected>Selecciona una categoría…</option>
                                @foreach ($categorias->where('estado', true) as $categoria)
                                    <option value="{{ $categoria->id }}" @selected(old('idcategoria') == $categoria->id)>
                                        {{ $categoria->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                @if ($puedeCrearCategoria)
                                    Si no encuentras la categoría, créala con el botón "+ Categoría".
                                @else
                                    Solo aparecen las categorías activas.
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto</label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Opcional. JPG, PNG o WEBP de hasta 2 MB.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarProductoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            @if ($puedeEditarProducto)
                <form method="POST" id="form-editar-producto" action="{{ route('dashboard.productos.update', 'ID') }}"
                      enctype="multipart/form-data" data-update-url-template="{{ route('dashboard.productos.update', 'ID') }}">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Editar producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="editarProductoNombre" class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="editarProductoNombre" class="form-control"
                                       maxlength="100" required placeholder="Nombre del producto">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="editarProductoCategoria" class="form-label">Categoría *</label>
                                <select name="idcategoria" id="editarProductoCategoria" class="form-select" required>
                                    @foreach ($categorias->where('estado', true) as $categoria)
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Solo se pueden elegir categorías activas.</div>
                            </div>
                            <div class="col-12">
                                <label for="editarProductoDescripcion" class="form-label">Descripción *</label>
                                <textarea name="descripcion" id="editarProductoDescripcion" rows="3"
                                          class="form-control" maxlength="200" required
                                          placeholder="Breve descripción del producto…"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">Foto del producto</label>
                                <div id="fotoActual" class="d-none align-items-start gap-3 p-3 bg-light border rounded mb-3">
                                    <div class="flex-shrink-0">
                                        <img id="fotoActualImg" src="" alt="Foto actual del producto"
                                             class="rounded border object-fit-cover bg-white"
                                             style="width: 96px; height: 96px;">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">Foto actual</div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="quitar_photo"
                                                   id="quitarPhoto" value="1">
                                            <label class="form-check-label text-danger" for="quitarPhoto">
                                                Quitar la foto actual
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <label for="editarProductoPhoto" class="form-label">Cambiar foto</label>
                                <input type="file" name="photo" id="editarProductoPhoto" class="form-control"
                                       accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG, PNG o WEBP de hasta 2 MB. Déjalo vacío para conservar la foto actual.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Actualizar producto</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para editar productos.
                        Solicita al administrador el permiso <strong>311</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>
