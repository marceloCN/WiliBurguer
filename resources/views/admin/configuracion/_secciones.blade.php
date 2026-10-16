<div class="tab-pane fade" id="tab-secciones" role="tabpanel" aria-labelledby="tab-secciones-tab">
    <div class="card tarjeta-producto mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-1">Secciones de la página de inicio</h2>
            <p class="text-muted small mb-4">Activa o desactiva cada bloque de la portada.</p>

            <div class="vstack gap-3">
                @foreach ([
                    'SITIO_HABILITAR_MENU' => 'Menú de productos por sucursal',
                    'SITIO_HABILITAR_PROMOCIONES' => 'Promociones por sucursal',
                    'SITIO_HABILITAR_SUCURSALES' => 'Sucursales, mapas y horarios',
                ] as $clave => $etiqueta)
                    <label class="switch-card d-flex align-items-center justify-content-between p-3 border rounded-3 cursor-pointer"
                           for="{{ $clave }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $etiqueta }}</span>
                            <span class="text-muted small">Sección visible en la página de inicio.</span>
                        </div>
                        <div class="form-check form-switch form-switch-xl m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="{{ $clave }}"
                                   name="{{ $clave }}" value="1"
                                   @checked($configuracion->get($clave) === '1')>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
</div>
