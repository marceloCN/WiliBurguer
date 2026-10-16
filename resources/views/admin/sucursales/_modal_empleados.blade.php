{{-- Modal para mostrar la lista de empleados de una sucursal --}}
<div class="modal fade" id="modalEmpleadosSucursal" tabindex="-1" aria-labelledby="modalEmpleadosSucursalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalEmpleadosSucursalLabel">Empleados de la sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3" id="modal-empleados-subtitulo"></p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Empleado</th>
                                <th>Rol</th>
                            </tr>
                        </thead>
                        <tbody id="modal-empleados-body">
                            {{-- Se llena dinámicamente --}}
                        </tbody>
                    </table>
                </div>

                <div id="modal-empleados-vacio" class="alert alert-light text-center d-none">
                    Esta sucursal no tiene empleados asignados.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
