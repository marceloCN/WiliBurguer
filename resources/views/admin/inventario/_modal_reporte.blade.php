<div class="modal fade" id="reporteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if ($puedeReporte)
                <form method="GET" action="{{ route('dashboard.inventario.reporte') }}" id="form-reporte-pdf">
                    @if (! $esAdmin)
                        <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">
                    @endif
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Reportes PDF · {{ $sucursalActual->nombre }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-light border small mb-3">
                            Descarga un PDF con el estado actual del inventario o con los movimientos
                            (entradas, salidas y ajustes) de <strong>{{ $sucursalActual->nombre }}</strong>.
                        </div>
                        <div class="row g-3">
                            @if ($esAdmin && $sucursales->count() > 1)
                                <div class="col-12">
                                    <label class="form-label">Sucursal *</label>
                                    <select name="idsucursal" class="form-select" required>
                                        @foreach ($sucursales as $sucursal)
                                            <option value="{{ $sucursal->id }}" @selected($sucursalActual->id === $sucursal->id)>
                                                {{ $sucursal->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-12">
                                <label class="form-label">Tipo de detalle *</label>
                                <select name="tipo" class="form-select" id="reporteTipo" required>
                                    <option value="estado">Estado actual de inventario</option>
                                    <option value="movimientos">Movimientos de entrada, salida y ajuste</option>
                                </select>
                            </div>
                            <div class="col-12" id="reporteFechaDesde" style="display:none;">
                                <label class="form-label">Desde qué fecha *</label>
                                <input type="date" name="fecha_desde" class="form-control"
                                       value="{{ old('fecha_desde', now()->startOfMonth()->format('Y-m-d')) }}">
                                <div class="form-text">Se listarán los movimientos desde esa fecha hasta hoy.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Descargar PDF</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Reportes PDF · {{ $sucursalActual->nombre }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para generar reportes PDF del inventario.
                        Solicita al administrador el permiso <strong>349</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>
