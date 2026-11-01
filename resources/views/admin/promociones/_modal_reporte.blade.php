<div class="modal fade" id="reporteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="GET" action="{{ route('dashboard.promociones.reporte') }}" id="form-reporte-promociones">
                <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Reporte de productos promocionados · {{ $sucursalActual->nombre }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small mb-3">
                        Descarga un PDF con las <strong>promociones</strong> de {{ $sucursalActual->nombre }}
                        cuya vigencia toque el rango de fechas elegido, junto con sus productos y descuentos.
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Desde *</label>
                            <input type="date" name="fecha_desde" class="form-control"
                                   value="{{ old('fecha_desde', now()->startOfMonth()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Hasta *</label>
                            <input type="date" name="fecha_hasta" class="form-control"
                                   value="{{ old('fecha_hasta', now()->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="form-text">
                        Se listan las promociones vigentes o que solapan ese período.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Descargar PDF</button>
                </div>
            </form>
        </div>
    </div>
</div>
