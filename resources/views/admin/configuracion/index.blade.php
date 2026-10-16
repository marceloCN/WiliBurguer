@extends('layouts.app')

@section('title', 'Configuración del sitio · '.$configuracionSitio->get('SITIO_NOMBRE'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-0">Configuración del sitio</h1>
                <p class="text-muted mb-0">Personaliza la página de inicio: datos, carrusel, secciones y tema.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">← Volver al panel</a>
        </div>

        @if (session('exito'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('exito') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Revisa los datos:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        @endif

        @include('admin.configuracion._nav')

        <form method="POST" action="{{ route('dashboard.configuracion.update') }}">
            @csrf

            <div class="tab-content" id="configTabsContent">
                @include('admin.configuracion._identidad')
                @include('admin.configuracion._carrusel')
                @include('admin.configuracion._secciones')
                @include('admin.configuracion._tema')
                @include('admin.configuracion._mantenimiento')
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-brand px-4">Guardar cambios</button>
            </div>
        </form>
    </div>
@endsection

@push('styles')
    <style>
        /* Switches de tamaño grande y mejor estilo */
        .form-switch-xl {
            padding-left: 3.5rem;
            min-height: 1.8rem;
        }
        .form-switch-xl .form-check-input {
            width: 3rem;
            height: 1.6rem;
            margin-left: -3.5rem;
            border-radius: 1rem;
            cursor: pointer;
        }
        .form-switch-xl .form-check-input:checked {
            background-color: var(--brand);
            border-color: var(--brand);
        }
        .form-switch-xl .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(var(--brand-rgb), 0.25);
        }

        /* Tarjeta clickable para switches */
        .switch-card {
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background-color: #fff;
        }
        .switch-card:hover {
            border-color: var(--brand) !important;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        }
        .switch-card .form-check-input {
            pointer-events: none;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        $(document).ready(function () {
            // ============ Carrusel: funciones expuestas en window para handlers inline ============
            function quitarFila(btn) {
                $(btn).closest('[data-slider-fila]').remove();
                renumerarDiapositivas();
            }

            function sliderImagenCargada(img) {
                var $marco = $(img).closest('[data-slider-vista]');
                if (!$marco.length) return;
                $(img).removeClass('d-none');
                $marco.find('.slider-vista-fallback').addClass('d-none');
            }

            function sliderImagenRota(img) {
                var $marco = $(img).closest('[data-slider-vista]');
                if (!$marco.length) return;
                $(img).addClass('d-none');
                $marco.find('.slider-vista-fallback').removeClass('d-none');
            }

            function renumerarDiapositivas() {
                $('#slidersContainer [data-slider-fila]').each(function (i) {
                    $(this).find('.badge-diapositiva').text('Diapositiva #' + (i + 1));
                });
            }

            function actualizarVistaSlider(input) {
                var $fila = $(input).closest('[data-slider-fila]');
                var $marco = $fila.find('[data-slider-vista]');
                if (!$marco.length) return;
                var url = $(input).val().trim();
                if (!url) {
                    $marco.addClass('d-none');
                    return;
                }
                $marco.removeClass('d-none');
                var $img = $marco.find('.slider-vista-img');
                sliderImagenRota($img[0]);
                $img.attr('src', url);
            }

            window.quitarFila = quitarFila;
            window.sliderImagenCargada = sliderImagenCargada;
            window.sliderImagenRota = sliderImagenRota;

            // ============ Carrusel: agregar / quitar diapositivas ============
            function agregarDiapositiva() {
                var $contenedor = $('#slidersContainer');
                if (!$contenedor.length) return;
                var $fila = $(
                    '<div class="card tarjeta-producto" data-slider-fila>' +
                    '    <div class="card-body p-3">' +
                    '        <div class="d-flex justify-content-between align-items-start mb-2">' +
                    '            <span class="badge text-bg-brand badge-diapositiva">Diapositiva</span>' +
                    '            <button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarFila(this)">Quitar</button>' +
                    '        </div>' +
                    '        <div class="row g-3">' +
                    '            <div class="col-md-7">' +
                    '                <div class="vstack gap-3">' +
                    '                    <div>' +
                    '                        <label class="form-label small fw-bold mb-1">URL de la imagen</label>' +
                    '                        <input type="text" name="slider[imagen][]" class="form-control" placeholder="https://ejemplo.com/imagen.jpg">' +
                    '                    </div>' +
                    '                    <div class="row g-3">' +
                    '                        <div class="col-md-6">' +
                    '                            <label class="form-label small fw-bold mb-1">Título</label>' +
                    '                            <input type="text" name="slider[titulo][]" class="form-control" maxlength="255" placeholder="Texto principal">' +
                    '                        </div>' +
                    '                        <div class="col-md-6">' +
                    '                            <label class="form-label small fw-bold mb-1">Subtítulo</label>' +
                    '                            <input type="text" name="slider[subtitulo][]" class="form-control" maxlength="255" placeholder="Texto secundario">' +
                    '                        </div>' +
                    '                    </div>' +
                    '                    <div class="row g-3">' +
                    '                        <div class="col-md-6">' +
                    '                            <label class="form-label small fw-bold mb-1">Texto del botón</label>' +
                    '                            <input type="text" name="slider[boton_texto][]" class="form-control" maxlength="100" placeholder="Ver menú">' +
                    '                        </div>' +
                    '                        <div class="col-md-6">' +
                    '                            <label class="form-label small fw-bold mb-1">Destino del botón (#id o URL)</label>' +
                    '                            <input type="text" name="slider[boton_url][]" class="form-control" maxlength="255" placeholder="#menu">' +
                    '                        </div>' +
                    '                    </div>' +
                    '                </div>' +
                    '            </div>' +
                    '            <div class="col-md-5">' +
                    '                <label class="form-label small fw-bold mb-1 d-block">Vista previa</label>' +
                    '                <div class="slider-vista d-none" data-slider-vista>' +
                    '                    <div class="slider-vista-marco">' +
                    '                        <span class="slider-vista-fallback">🍔</span>' +
                    '                        <img class="slider-vista-img d-none" alt="Vista previa de la diapositiva"' +
                    '                             onload="sliderImagenCargada(this)" onerror="sliderImagenRota(this)">' +
                    '                    </div>' +
                    '                </div>' +
                    '                <div class="form-text">Vista previa automática según la URL ingresada.</div>' +
                    '            </div>' +
                    '        </div>' +
                    '    </div>' +
                    '</div>'
                );
                $contenedor.append($fila);
                renumerarDiapositivas();
                $fila[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            $('#agregarSlider').on('click', agregarDiapositiva);

            // Vista previa en vivo al escribir la URL (incluye diapositivas agregadas dinámicamente)
            $('#slidersContainer')
                .on('input', 'input[name="slider[imagen][]"]', function () {
                    actualizarVistaSlider(this);
                })
                .find('input[name="slider[imagen][]"]')
                .each(function () { actualizarVistaSlider(this); });

            renumerarDiapositivas();

            // ============ Abrir pestaña desde el hash del submenú ============
            try {
                var hash = window.location.hash;
                if (hash) {
                    var $pestana = $('[data-bs-target="' + hash + '"]');
                    if ($pestana.length) {
                        bootstrap.Tab.getOrCreateInstance($pestana[0]).show();
                    }
                }
            } catch (e) {
                // Sin bootstrap o elemento inválido: no romper el resto de la página.
            }

            $('#configTabs [data-bs-toggle="pill"]').on('click', function () {
                history.replaceState(null, '', $(this).data('bsTarget'));
            });

            // ============ Tema: preset y vista previa ============
            var campos = {};
            ['TEMA_COLOR_PRIMARIO', 'TEMA_COLOR_PRIMARIO_OSCURO', 'TEMA_COLOR_FONDO', 'TEMA_COLOR_TEXTO', 'TEMA_COLOR_DETALLE']
                .forEach(function (clave) {
                    campos[clave] = {
                        color: $('input[name="' + clave + '"][type="color"]'),
                        codigo: $('#' + clave),
                    };
                    if (campos[clave].color.length && campos[clave].codigo.length) {
                        campos[clave].color.on('input', function () {
                            campos[clave].codigo.val(campos[clave].color.val());
                            aplicarVistaPrevia();
                        });
                        campos[clave].codigo.on('input', function () {
                            if (/^#[0-9a-fA-F]{6}$/.test(campos[clave].codigo.val())) {
                                campos[clave].color.val(campos[clave].codigo.val());
                                aplicarVistaPrevia();
                            }
                        });
                    }
                });

            var presets = {
                verde: { TEMA_COLOR_PRIMARIO: '#4a6741', TEMA_COLOR_PRIMARIO_OSCURO: '#3a5233', TEMA_COLOR_FONDO: '#faf6ef', TEMA_COLOR_TEXTO: '#2f2b26', TEMA_COLOR_DETALLE: '#d4a373' },
                rojo: { TEMA_COLOR_PRIMARIO: '#b23a3a', TEMA_COLOR_PRIMARIO_OSCURO: '#8f2d2d', TEMA_COLOR_FONDO: '#fdf5f3', TEMA_COLOR_TEXTO: '#331d1d', TEMA_COLOR_DETALLE: '#e8a87c' },
                azul: { TEMA_COLOR_PRIMARIO: '#2f5d75', TEMA_COLOR_PRIMARIO_OSCURO: '#24485c', TEMA_COLOR_FONDO: '#f2f7f9', TEMA_COLOR_TEXTO: '#132533', TEMA_COLOR_DETALLE: '#d9a441' },
                ambar: { TEMA_COLOR_PRIMARIO: '#c96f2e', TEMA_COLOR_PRIMARIO_OSCURO: '#a75820', TEMA_COLOR_FONDO: '#fdf6ec', TEMA_COLOR_TEXTO: '#33261a', TEMA_COLOR_DETALLE: '#7ea16b' },
                noche: { TEMA_COLOR_PRIMARIO: '#4b5563', TEMA_COLOR_PRIMARIO_OSCURO: '#374151', TEMA_COLOR_FONDO: '#f4f5f7', TEMA_COLOR_TEXTO: '#1f2937', TEMA_COLOR_DETALLE: '#10b981' },
            };

            $('.preset-tema').on('click', function () {
                var preset = presets[$(this).data('preset')];
                if (!preset) return;
                Object.keys(preset).forEach(function (clave) {
                    campos[clave].color.val(preset[clave]);
                    campos[clave].codigo.val(preset[clave]);
                });
                aplicarVistaPrevia();
            });

            function aplicarVistaPrevia() {
                if (!campos.TEMA_COLOR_PRIMARIO || !campos.TEMA_COLOR_PRIMARIO.color || !campos.TEMA_COLOR_PRIMARIO.color.length) return;
                var v = {};
                Object.keys(campos).forEach(function (k) { v[k] = campos[k].color.val(); });
                $('#previewBarra').css('backgroundColor', v.TEMA_COLOR_PRIMARIO);
                $('#previewCuerpo').css({ backgroundColor: v.TEMA_COLOR_FONDO, color: v.TEMA_COLOR_TEXTO });
                $('#previewBadcillo').css('color', v.TEMA_COLOR_PRIMARIO);
                $('#previewTitulo').css('color', v.TEMA_COLOR_TEXTO);
                $('#previewTexto').css('color', v.TEMA_COLOR_TEXTO);
                $('#previewBoton').css({ backgroundColor: v.TEMA_COLOR_PRIMARIO, borderColor: v.TEMA_COLOR_PRIMARIO, color: '#fff' });
                $('#previewChip').css({ backgroundColor: v.TEMA_COLOR_PRIMARIO, color: '#fff' });
                $('#previewChip2').css({ color: v.TEMA_COLOR_PRIMARIO, border: '1px solid ' + v.TEMA_COLOR_PRIMARIO });
            }

            aplicarVistaPrevia();
        });
    </script>
@endpush
