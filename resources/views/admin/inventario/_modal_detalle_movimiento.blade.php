<div class="modal fade" id="detalleMovimientoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Detalle del movimiento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Fecha</span>
                        <span class="fw-semibold" id="detalle-fecha"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Tipo</span>
                        <span id="detalle-tipo"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Insumo</span>
                        <span class="fw-semibold" id="detalle-insumo"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Unidad</span>
                        <span id="detalle-unidad"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Cantidad</span>
                        <span class="fw-semibold" id="detalle-cantidad"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Stock anterior</span>
                        <span id="detalle-anterior"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Stock nuevo</span>
                        <span class="fw-semibold" id="detalle-nuevo"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Empleado</span>
                        <span id="detalle-empleado"></span>
                    </li>
                    <li class="list-group-item px-0">
                        <span class="text-muted d-block mb-1">Observación</span>
                        <span id="detalle-observacion"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
