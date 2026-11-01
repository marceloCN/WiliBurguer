<div class="modal fade" id="nuevaCategoriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('dashboard.productos.categoria.store') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Nueva categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" maxlength="100" required
                           value="{{ old('nombre') }}" placeholder="Ej.: Hamburguesas, Porciones, Gaseosas">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarCategoriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if ($puedeEditarCategoria)
                <form method="POST" id="form-editar-categoria">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Editar categoría</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="nombre" id="editarCategoriaNombre" class="form-control"
                               maxlength="100" required>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Actualizar categoría</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para editar categorías.
                        Solicita al administrador el permiso <strong>301</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>
