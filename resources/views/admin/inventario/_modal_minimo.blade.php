<div class="modal fade" id="editarMinimoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="form-editar-minimo">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Cantidad mínima de inventario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Cuando el stock de <strong id="minimoInsumoNombre"></strong> sea
                        <strong>menor o igual</strong> a la cantidad mínima, se mostrará la alerta de stock bajo.
                    </p>
                    <label class="form-label">Cantidad mínima</label>
                    <input type="number" name="cantidadminima" id="minimoCantidad" class="form-control"
                           step="0.001" min="0" placeholder="Deja vacío para sin mínimo">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Guardar mínimo</button>
                </div>
            </form>
        </div>
    </div>
</div>
