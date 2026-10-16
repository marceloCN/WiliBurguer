@php($urlImagen = old('slider.imagen.'.$indice, $slider['imagen'] ?? ''))
<div class="card tarjeta-producto" data-slider-fila>
    <div class="card-body p-3">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge text-bg-brand badge-diapositiva">Diapositiva #{{ $indice + 1 }}</span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarFila(this)">Quitar</button>
        </div>
        <div class="row g-3">
            <div class="col-md-7">
                <div class="vstack gap-3">
                    <div>
                        <label class="form-label small fw-bold mb-1">URL de la imagen</label>
                        <input type="text" name="slider[imagen][]" class="form-control"
                               placeholder="https://ejemplo.com/imagen.jpg"
                               value="{{ $urlImagen }}">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Título</label>
                            <input type="text" name="slider[titulo][]" class="form-control" maxlength="255" placeholder="Texto principal"
                                   value="{{ old('slider.titulo.'.$indice, $slider['titulo'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Subtítulo</label>
                            <input type="text" name="slider[subtitulo][]" class="form-control" maxlength="255" placeholder="Texto secundario"
                                   value="{{ old('slider.subtitulo.'.$indice, $slider['subtitulo'] ?? '') }}">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Texto del botón</label>
                            <input type="text" name="slider[boton_texto][]" class="form-control" maxlength="100" placeholder="Ver menú"
                                   value="{{ old('slider.boton_texto.'.$indice, $slider['boton_texto'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold mb-1">Destino del botón (#id o URL)</label>
                            <input type="text" name="slider[boton_url][]" class="form-control" maxlength="255" placeholder="#menu"
                                   value="{{ old('slider.boton_url.'.$indice, $slider['boton_url'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold mb-1 d-block">Vista previa</label>
                <div class="slider-vista {{ $urlImagen === '' ? 'd-none' : '' }}" data-slider-vista>
                    <div class="slider-vista-marco">
                        <span class="slider-vista-fallback">🍔</span>
                        <img class="slider-vista-img d-none" alt="Vista previa de la diapositiva"
                             onload="sliderImagenCargada(this)" onerror="sliderImagenRota(this)">
                    </div>
                </div>
                <div class="form-text">Vista previa automática según la URL ingresada.</div>
            </div>
        </div>
    </div>
</div>