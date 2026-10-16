<div class="tab-pane fade" id="tab-tema" role="tabpanel" aria-labelledby="tab-tema-tab">
    <div class="card tarjeta-producto mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-1">Tema y colores</h2>
            <p class="text-muted small mb-4">Cambia el aspecto visual. Compatible con cualquier tipo de servicio: elige tus propios colores o usa un preset.</p>

            <div class="mb-4">
                <label class="form-label fw-semibold">Presets rápidos</label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary preset-tema" data-preset="verde">Verde (burger)</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary preset-tema" data-preset="rojo">Rojo</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary preset-tema" data-preset="azul">Azul</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary preset-tema" data-preset="ambar">Ámbar</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary preset-tema" data-preset="noche">Noche</button>
                </div>
            </div>

            <div class="row g-3">
                @foreach ([
                    'TEMA_COLOR_PRIMARIO' => 'Color principal',
                    'TEMA_COLOR_PRIMARIO_OSCURO' => 'Primario oscuro',
                    'TEMA_COLOR_FONDO' => 'Fondo de la página',
                    'TEMA_COLOR_TEXTO' => 'Color de texto',
                    'TEMA_COLOR_DETALLE' => 'Color de detalle',
                ] as $clave => $etiqueta)
                    <div class="col-md-4 col-lg-4 col-6">
                        <label class="form-label small fw-bold">{{ $etiqueta }}</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color tema-color" data-texto="{{ $clave }}" name="{{ $clave }}"
                                   value="{{ $configuracion->get($clave) }}" title="{{ $etiqueta }}">
                            <input type="text" class="form-control tema-codigo" id="{{ $clave }}"
                                   value="{{ $configuracion->get($clave) }}" maxlength="7">
                        </div>
                    </div>
                @endforeach
            </div>

            <hr class="my-4">

            <h3 class="h6 fw-bold mb-2">Vista previa</h3>
            <div id="previewTema" class="card border-0 shadow-sm overflow-hidden">
                <div id="previewBarra" class="px-3 py-2 d-flex justify-content-between align-items-center text-white">
                    <span class="fw-bold">🥗 Mi negocio de comidas</span>
                    <span class="badge text-bg-light">Inicio</span>
                </div>
                <div id="previewCuerpo" class="p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <div class="small text-uppercase fw-bold" id="previewBadcillo">Bienvenido</div>
                            <h3 class="fw-bolder mb-1" id="previewTitulo">Burguer Willy and Grill</h3>
                            <p class="small mb-0" id="previewTexto">Personaliza colores, textos e imágenes desde el panel.</p>
                        </div>
                        <button type="button" class="btn btn-sm" id="previewBoton">Ver menú</button>
                    </div>
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                        <span class="badge" id="previewChip">Todos</span>
                        <span class="badge border" id="previewChip2">Hamburguesas</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
