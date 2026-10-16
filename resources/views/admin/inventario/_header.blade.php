<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Inventario</h1>
        <p class="text-muted mb-0">Gestión de stock, movimientos y reportes por sucursal.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-brand" data-bs-toggle="modal" data-bs-target="#reporteModal">
            Reportes PDF
        </button>
        <button type="button" class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#nuevoMovimientoModal">
            + Registrar movimiento
        </button>
    </div>
</div>

<div class="alert alert-light border small mb-4">
    Cada registro es un <strong>movimiento</strong> que actualiza el stock:
    <span class="badge text-bg-success">ENTRADA</span> suma,
    <span class="badge text-bg-danger">SALIDA</span> resta y
    <span class="badge text-bg-warning">AJUSTE</span> deja la cantidad exacta.
</div>
