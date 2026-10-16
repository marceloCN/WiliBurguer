<div class="modal fade" id="editarSeguridadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Cambiar contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="editarSeguridadForm">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Empleado: <strong id="seguridadNombreEmpleado"></strong><br>
                        Usuario: <strong id="seguridadUsuarioEmpleado"></strong>
                    </p>
                    <label class="form-label">Nueva contraseña *</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>
