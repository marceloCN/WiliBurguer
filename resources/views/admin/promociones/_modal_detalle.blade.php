<div class="modal fade" id="detallePromocionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Detalle de la promoción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="detallePromocionFoto" src="" alt="Foto de la promoción"
                         class="rounded border object-fit-cover bg-light d-none"
                         style="width: 160px; height: 160px;">
                    <div id="detallePromocionSinFoto" class="d-flex align-items-center justify-content-center rounded border bg-light mx-auto"
                         style="width: 160px; height: 160px; font-size: 4rem;">
                        🎉
                    </div>
                </div>

                <h4 id="detallePromocionNombre" class="fw-bold text-center mb-2"></h4>

                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <span id="detallePromocionDescuento" class="badge text-bg-danger"></span>
                    <span id="detallePromocionEstado" class="badge"></span>
                    <span id="detallePromocionVigencia" class="badge text-bg-light border"></span>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold small text-muted mb-2">DÍAS EN QUE APLICA</div>
                    <div id="detallePromocionDias" class="d-flex flex-wrap gap-1"></div>
                </div>

                <div class="mb-3">
                    <div class="fw-semibold small text-muted mb-2">PRODUCTOS PARTICIPANTES</div>
                    <ul id="detallePromocionProductos" class="list-group list-group-flush border rounded"></ul>
                </div>

                <div id="detallePromocionAplicaHoy" class="alert alert-success d-none">
                    Esta promoción aplica hoy.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
