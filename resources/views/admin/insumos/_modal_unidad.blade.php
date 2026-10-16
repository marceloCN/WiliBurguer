<div class="modal fade" id="nuevaUnidadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('dashboard.insumos.unidad.store') }}">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Nueva unidad de medida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" class="form-control" maxlength="50" required
                                   value="{{ old('nombre') }}" placeholder="Ej.: Kilogramo, Litro, Unidad">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Abreviatura *</label>
                            <input type="text" name="abreviatura" class="form-control" maxlength="10" required
                                   value="{{ old('abreviatura') }}" placeholder="Ej.: kg, l, und">
                            <div class="form-text">Se guardará en minúsculas.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tipo *</label>
                            <select name="tipo" class="form-select" required>
                                <option value="" disabled selected>Selecciona un tipo…</option>
                                @foreach ($tiposUnidad as $clave => $etiqueta)
                                    <option value="{{ $clave }}" @selected(old('tipo') == $clave)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar unidad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarUnidadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if ($puedeEditarUnidad)
                <form method="POST" id="form-editar-unidad">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Editar unidad de medida</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="nombre" id="editarUnidadNombre" class="form-control" maxlength="50" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Abreviatura *</label>
                                <input type="text" name="abreviatura" id="editarUnidadAbreviatura" class="form-control" maxlength="10" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tipo *</label>
                                <select name="tipo" id="editarUnidadTipo" class="form-select" required>
                                    @foreach ($tiposUnidad as $clave => $etiqueta)
                                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Actualizar unidad</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar unidad de medida</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para editar unidades de medida.
                        Solicita al administrador el permiso <strong>331</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>
