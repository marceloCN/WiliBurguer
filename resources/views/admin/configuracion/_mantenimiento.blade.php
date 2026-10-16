<div class="tab-pane fade" id="tab-mantenimiento" role="tabpanel" aria-labelledby="tab-mantenimiento-tab">
    <div class="card tarjeta-producto mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-1">Modo mantenimiento</h2>
            <p class="text-muted small mb-4">Al activarlo, los visitantes y usuarios no administradores verán la página de mantenimiento en todo el sitio. El administrador siempre puede seguir accediendo para desactivarlo.</p>

            <label class="switch-card d-flex align-items-center justify-content-between p-3 border rounded-3 cursor-pointer mb-4"
                   for="SITIO_MANTENIMIENTO_ACTIVO">
                <div>
                    <span class="fw-semibold d-block">Activar mantenimiento de la página</span>
                    <span class="text-muted small">Solo los administradores podrán navegar el sitio.</span>
                </div>
                <div class="form-check form-switch form-switch-xl m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="SITIO_MANTENIMIENTO_ACTIVO"
                           name="SITIO_MANTENIMIENTO_ACTIVO" value="1"
                           @checked($configuracion->get('SITIO_MANTENIMIENTO_ACTIVO') === '1')>
                </div>
            </label>

            <label class="form-label fw-semibold">Mensaje de mantenimiento</label>
            <textarea name="SITIO_MANTENIMIENTO_MENSAJE" class="form-control" rows="3" maxlength="500"
                      placeholder="Estamos realizando mejoras en la página. Volveremos muy pronto.">{{ $configuracion->get('SITIO_MANTENIMIENTO_MENSAJE') }}</textarea>
        </div>
    </div>
</div>
