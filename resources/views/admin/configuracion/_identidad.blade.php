<div class="tab-pane fade show active" id="tab-identidad" role="tabpanel" aria-labelledby="tab-identidad-tab">
    <div class="card tarjeta-producto mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-1">Identidad del negocio</h2>
            <p class="text-muted small mb-4">Estos datos aparecen en la página de inicio, la barra de navegación y el pie de página.</p>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nombre del negocio *</label>
                    <input type="text" name="SITIO_NOMBRE" class="form-control" maxlength="255"
                           value="{{ $configuracion->get('SITIO_NOMBRE') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="SITIO_TELEFONO" class="form-control" maxlength="255"
                           value="{{ $configuracion->get('SITIO_TELEFONO') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Eslogan</label>
                    <input type="text" name="SITIO_ESLOGAN" class="form-control" maxlength="255"
                           value="{{ $configuracion->get('SITIO_ESLOGAN') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo de contacto</label>
                    <input type="email" name="SITIO_EMAIL" class="form-control" maxlength="255"
                           value="{{ $configuracion->get('SITIO_EMAIL') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="SITIO_DIRECCION" class="form-control" maxlength="255"
                           value="{{ $configuracion->get('SITIO_DIRECCION') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Horario general</label>
                    <input type="text" name="SITIO_HORARIO" class="form-control" maxlength="255"
                           value="{{ $configuracion->get('SITIO_HORARIO') }}">
                </div>
            </div>
        </div>
    </div>
</div>
