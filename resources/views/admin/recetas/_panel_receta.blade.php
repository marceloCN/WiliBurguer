<div class="offcanvas offcanvas-end" tabindex="-1" id="panelReceta" aria-labelledby="panel-receta-titulo">
    <div class="offcanvas-header bg-light border-bottom">
        <div class="me-3">
            <h5 class="offcanvas-title fw-bold mb-0" id="panel-receta-titulo">Receta del producto</h5>
            <div class="small text-muted" id="panel-receta-subtitulo">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        @if ($errors->any())
            <div class="alert alert-danger small">
                <strong>Revisa los datos:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold small text-muted">INSUMOS DE LA RECETA</span>
            <span class="badge text-bg-success" id="panel-receta-total">0</span>
        </div>

        <div class="alert alert-warning small d-none mb-3" id="panel-receta-vacio">
            Este producto todavía no tiene insumos. Agrega el primero con el formulario de abajo.
        </div>

        <ul class="list-group mb-4" id="panel-receta-lista"></ul>

        @if ($puedeCrear)
            <div class="card border">
                <div class="card-header bg-light fw-bold small">Agregar insumo a la receta</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('dashboard.recetas.store') }}" id="form-agregar-receta">
                        @csrf
                        <input type="hidden" name="idproducto" id="receta-idproducto" value="{{ old('idproducto') }}">
                        <div class="mb-3">
                            <label class="form-label" for="receta-insumo">Insumo *</label>
                            <select name="idinsumo" id="receta-insumo" class="form-select" required>
                                <option value="" disabled @selected(! old('idinsumo'))>Selecciona un insumo…</option>
                                @foreach ($insumos->where('estado', true) as $insumo)
                                    <option value="{{ $insumo->id }}" @selected(old('idinsumo') == $insumo->id)>
                                        {{ $insumo->nombre }} ({{ $insumo->unidadMedida?->abreviatura ?? '—' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="receta-cantidad">Cantidad *</label>
                            <input type="number" name="cantidad" id="receta-cantidad" class="form-control"
                                   min="0.001" step="0.001" required value="{{ old('cantidad') }}"
                                   placeholder="Ej.: 0.250">
                            <div class="form-text">
                                En la unidad de medida del insumo (kg, ml, und, etc.).
                            </div>
                        </div>
                        <button type="submit" class="btn btn-brand w-100">Agregar a la receta</button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-warning mb-0 small">
                <strong>Sin permiso.</strong>
                No tienes permiso para agregar insumos a las recetas.
                Solicita al administrador el permiso <strong>340</strong> si necesitas hacerlo.
            </div>
        @endif
    </div>
</div>
