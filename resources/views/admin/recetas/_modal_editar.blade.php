<div class="modal fade" id="editarRecetaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if ($puedeEditar)
                <form method="POST" id="form-editar-receta"
                      action="{{ route('dashboard.recetas.update', '__DETALLE__') }}">
                    @csrf
                    <input type="hidden" name="idproducto" id="editar-receta-producto">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Editar detalle de receta</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="editar-receta-insumo">Insumo *</label>
                                <select name="idinsumo" id="editar-receta-insumo" class="form-select" required>
                                    @foreach ($insumos as $insumo)
                                        <option value="{{ $insumo->id }}">
                                            {{ $insumo->nombre }} ({{ $insumo->unidadMedida?->abreviatura ?? '—' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="editar-receta-cantidad">Cantidad *</label>
                                <input type="number" name="cantidad" id="editar-receta-cantidad" class="form-control"
                                       min="0.001" step="0.001" required>
                                <div class="form-text">
                                    En la unidad de medida del insumo (kg, ml, und, etc.).
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Guardar cambios</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar detalle de receta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para modificar recetas.
                        Solicita al administrador el permiso <strong>341</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="confirmarBajaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="form-quitar-receta"
                  action="{{ route('dashboard.recetas.baja', '__DETALLE__') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Quitar insumo de la receta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="confirmar-baja-texto">
                        ¿Seguro que quieres quitar este insumo de la receta?
                    </p>
                    <div class="form-text">
                        Podrás volver a agregarlo más adelante si lo necesitas.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-outline-danger">Quitar insumo</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
