<div class="modal fade" id="nuevoMovimientoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if (! $puedeRegistrar)
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Registrar movimiento de inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para registrar movimientos de inventario.
                        Solicita al administrador el permiso <strong>346</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @elseif (! $hayInsumos)
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Registrar movimiento de inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>No hay insumos disponibles.</strong>
                        Primero crea insumos en <em>Insumos y recetas</em> para poder registrar movimientos de inventario.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @else
                <form method="POST" action="{{ route('dashboard.inventario.movimiento.store') }}" id="form-nuevo-movimiento">
                    @csrf
                    <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Registrar movimiento de inventario</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Sucursal</label>
                                <div class="fw-semibold text-muted">{{ $sucursalActual->nombre }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Insumo *</label>
                                <select name="idinsumo" id="select-insumo-movimiento" class="form-select" required>
                                    @if (old('idinsumo'))
                                        @php
                                            $insumoOld = \App\Models\Insumo::with('unidadMedida')->find(old('idinsumo'));
                                        @endphp
                                        @if ($insumoOld)
                                            <option value="{{ $insumoOld->id }}" selected>{{ $insumoOld->nombre }} ({{ $insumoOld->unidadMedida?->abreviatura ?? '—' }})</option>
                                        @endif
                                    @endif
                                </select>
                                <div class="form-text">Escribe para buscar insumos por nombre.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tipo de movimiento *</label>
                                <select name="tipo" class="form-select" required>
                                    @foreach (\App\Http\Controllers\InventarioController::TIPOS as $tipo)
                                        <option value="{{ $tipo }}" @selected(old('tipo') === $tipo)>{{ $tipo }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    Entrada: suma stock · Salida: resta stock · Ajuste: fija la cantidad exacta.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cantidad *</label>
                                <input type="number" name="cantidad" class="form-control" required
                                       step="0.001" min="0.001" value="{{ old('cantidad') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Cantidad mínima</label>
                                <input type="number" name="cantidadminima" class="form-control"
                                       step="0.001" min="0" placeholder="Solo al crear el insumo en esta sucursal"
                                       value="{{ old('cantidadminima') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Observación</label>
                                <textarea name="observacion" rows="2" class="form-control" maxlength="255"
                                          placeholder="Ej.: Compra semanal, merma, conteo físico…">{{ old('observacion') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Registrar movimiento</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<script type="module">
    new window.TomSelect('#select-insumo-movimiento', {
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        placeholder: 'Selecciona un insumo…',
        loadThrottle: 300,
        minItems: 1,
        maxItems: 1,
        load: function (query, callback) {
            const url = new URL('{{ route('dashboard.inventario.insumos.buscar') }}', window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('idsucursal', {{ $sucursalActual->id }});

            fetch(url)
                .then(response => response.json())
                .then(json => callback(json))
                .catch(() => callback());
        },
        render: {
            option: function (item, escape) {
                return `<div>${escape(item.text)}${item.nuevo ? ' <span class="badge text-bg-light border ms-1">nuevo en esta sucursal</span>' : ''}</div>`;
            },
            item: function (item, escape) {
                return `<div>${escape(item.text)}${item.nuevo ? ' <span class="badge text-bg-light border ms-1">nuevo</span>' : ''}</div>`;
            }
        }
    });
</script>
