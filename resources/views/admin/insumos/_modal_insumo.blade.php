<div class="modal fade" id="nuevoInsumoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('dashboard.insumos.store') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Nuevo insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" maxlength="100" required
                                   value="{{ old('nombre') }}" placeholder="Ej.: Carne molida, Tomate, Aceite">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Unidad de medida *</label>
                            <select name="idunidadmedida" class="form-select" required>
                                <option value="" disabled selected>Selecciona una unidad…</option>
                                @foreach ($unidades->where('estado', true) as $unidad)
                                    <option value="{{ $unidad->id }}" @selected(old('idunidadmedida') == $unidad->id)>
                                        {{ $unidad->nombre }} ({{ $unidad->abreviatura }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Si no encuentras la unidad, crea una con el botón "+ Unidad".
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" rows="2" class="form-control" maxlength="255"
                                      placeholder="Breve descripción del insumo…">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar insumo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarInsumoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if ($puedeEditarInsumo)
                <form method="POST" id="form-editar-insumo">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Editar insumo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="editarInsumoNombre" class="form-control" maxlength="100" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Unidad de medida *</label>
                                <select name="idunidadmedida" id="editarInsumoUnidad" class="form-select" required>
                                    @foreach ($unidades as $unidad)
                                        <option value="{{ $unidad->id }}">
                                            {{ $unidad->nombre }} ({{ $unidad->abreviatura }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="descripcion" id="editarInsumoDescripcion" rows="2" class="form-control" maxlength="255"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Actualizar insumo</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar insumo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para editar insumos.
                        Solicita al administrador el permiso <strong>337</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>
