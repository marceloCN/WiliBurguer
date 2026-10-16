<div class="tab-pane fade" id="tab-carrusel" role="tabpanel" aria-labelledby="tab-carrusel-tab">
    <div class="card tarjeta-producto mb-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-1">Carrusel de la portada</h2>
            <p class="text-muted small mb-3">Sube tus propias imágenes (URL pública de cada diapositiva) para que el carrusel se vea correctamente.</p>

            <div class="form-check form-switch form-switch-xl mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="SITIO_HABILITAR_CARRUSEL"
                       name="SITIO_HABILITAR_CARRUSEL" value="1"
                       @checked($configuracion->get('SITIO_HABILITAR_CARRUSEL') === '1')>
                <label class="form-check-label" for="SITIO_HABILITAR_CARRUSEL">
                    <span class="fw-semibold">Mostrar el carrusel de imágenes</span>
                    <span class="d-block text-muted small">Activa la sección de diapositivas en la página de inicio.</span>
                </label>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 fw-bold mb-0">Diapositivas</h3>
                <button type="button" class="btn btn-sm btn-outline-brand" id="agregarSlider">+ Agregar diapositiva</button>
            </div>

            <div id="slidersContainer" class="vstack gap-3">
                @forelse ($sliders as $slider)
                    @include('admin.configuracion._slider', ['slider' => $slider, 'indice' => $loop->index])
                @empty
                    @include('admin.configuracion._slider', ['slider' => [], 'indice' => 0])
                @endforelse
            </div>
        </div>
    </div>
</div>
