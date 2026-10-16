{{--
    Modal de detalle para bitácora de accesos.
    Se llena dinámicamente desde el script de la vista index mediante
    los atributos data-* del botón que lo abre.
--}}
<div class="modal fade" id="detalleAccesoModal" tabindex="-1" aria-labelledby="detalleAccesoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="detalleAccesoModalLabel">Detalle del acceso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Fecha y hora</span>
                        <span class="fw-semibold" id="modal-acceso-fecha"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Empleado</span>
                        <span class="fw-semibold" id="modal-acceso-empleado"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Tipo de acceso</span>
                        <span id="modal-acceso-tipo"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Resultado</span>
                        <span id="modal-acceso-resultado"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Equipo</span>
                        <span class="fw-semibold text-truncate ms-3" id="modal-acceso-equipo" style="max-width: 220px;"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">IP</span>
                        <span class="fw-semibold" id="modal-acceso-ip"></span>
                    </li>
                    <li class="list-group-item">
                        <span class="text-muted d-block mb-1">Detalle</span>
                        <span class="fw-semibold" id="modal-acceso-detalle"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
