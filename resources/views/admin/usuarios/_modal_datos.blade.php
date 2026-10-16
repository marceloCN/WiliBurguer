<div class="modal fade" id="editarDatosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Editar datos del usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="editarDatosForm">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Empleado: <strong id="datosNombreEmpleado"></strong><br>
                        Usuario: <strong id="datosUsuarioEmpleado"></strong>
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" name="nombre" id="datosNombre" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" name="apellido" id="datosApellido" class="form-control" required maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="telefono" id="datosTelefono" class="form-control" maxlength="15">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Usuario *</label>
                            <input type="text" name="usuario" id="datosUsuario" class="form-control" required maxlength="255">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Correo *</label>
                            <input type="email" name="correo" id="datosCorreo" class="form-control" required maxlength="255">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar datos</button>
                </div>
            </form>
        </div>
    </div>
</div>
