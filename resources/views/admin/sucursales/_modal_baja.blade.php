{{-- Modal compartido de confirmación de bajas (mesas, horarios, cajas) --}}
<div class="modal fade" id="modal-confirmar-baja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="form-confirmar-baja" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5">⚠️ Confirmar baja</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="texto-confirmar-baja"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">🗑️ Confirmar baja</button>
            </div>
        </form>
    </div>
</div>
