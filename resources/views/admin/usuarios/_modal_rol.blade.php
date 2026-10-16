<div class="modal fade" id="editarRolModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Cambiar rol</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="editarRolForm">
                @csrf
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Empleado: <strong id="rolNombreEmpleado"></strong><br>
                        Usuario: <strong id="rolUsuarioEmpleado"></strong>
                    </p>
                    <label class="form-label">Nuevo rol *</label>
                    <select name="idrol" id="rolSelect" class="form-select" required>
                        @foreach ($roles as $rol)
                            <option value="{{ $rol->id }}">{{ $rol->nombrerol }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar rol</button>
                </div>
            </form>
        </div>
    </div>
</div>
