{{--
    Modal de detalle para bitácora de acciones.
    Se llena dinámicamente desde el script de la vista index mediante
    los atributos data-* del botón que lo abre.
--}}
<div class="modal fade" id="detalleAccionModal" tabindex="-1" aria-labelledby="detalleAccionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="detalleAccionModalLabel">Detalle de la acción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Fecha y hora</span>
                        <span class="fw-semibold" id="modal-accion-fecha"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Empleado</span>
                        <span class="fw-semibold" id="modal-accion-empleado"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Permiso efectuado</span>
                        <span class="fw-semibold" id="modal-accion-permiso"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Código permiso</span>
                        <span class="fw-semibold" id="modal-accion-codigo"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Acción</span>
                        <span id="modal-accion-accion"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Tabla afectada</span>
                        <span class="fw-semibold" id="modal-accion-tabla"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Registro afectado</span>
                        <span class="fw-semibold" id="modal-accion-registro"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Equipo</span>
                        <span class="fw-semibold text-truncate ms-3" id="modal-accion-equipo" style="max-width: 220px;"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">IP</span>
                        <span class="fw-semibold" id="modal-accion-ip"></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Resultado</span>
                        <span id="modal-accion-resultado"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
