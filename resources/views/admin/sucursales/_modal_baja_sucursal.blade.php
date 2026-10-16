{{-- Modal para dar de baja o reactivar sucursal --}}
<div class="modal fade" id="modalBajaSucursal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="form-baja-sucursal" class="modal-content">
            @csrf
            <div class="modal-header bg-warning text-dark">
                <h2 class="modal-title fs-5 fw-bold">⚠️ Advertencia de cambio de estado</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-2" id="texto-baja-sucursal"></p>
                <div id="advertencia-baja-sucursal">
                    <p class="mb-2 small">Al dar de baja esta sucursal se ocasiona lo siguiente:</p>
                    <ul class="small mb-0">
                        <li>La sucursal dejará de operar en el sistema.</li>
                        <li>El gerente y sus empleados perderán acceso a esta sucursal.</li>
                        <li>El gerente quedará sin el módulo de sucursales y solo verá el dashboard.</li>
                        <li>Solo un administrador con permiso 205 podrá reactivarla.</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger" id="confirmar-baja-sucursal-btn">Confirmar</button>
            </div>
        </form>
    </div>
</div>

{{-- Aviso al gerente cuando su sucursal ya está desactivada --}}
<div class="modal fade" id="modalSucursalGerenteInactiva" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h2 class="modal-title fs-5 fw-bold">⛔ Sucursal desactivada</h2>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    Tu sucursal está <strong>desactivada</strong> en el sistema.
                </p>
                <p class="mb-0 small text-muted">
                    Contacta al administrador para que la reactive. Mientras tanto,
                    el módulo de sucursales no está disponible para tu usuario.
                </p>
            </div>
            <div class="modal-footer">
                <a href="{{ route('dashboard') }}" class="btn btn-brand">Ir al dashboard</a>
            </div>
        </div>
    </div>
</div>
