@extends('layouts.app')

@section('title', $configuracion->get('SITIO_NOMBRE', 'Burger Willy and Grill') . ' - ' . $configuracion->get('SITIO_ESLOGAN', 'Sabores que se quedan'))

@section('content')
    @if (session('exito'))
        <div class="container my-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('exito') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    @endif

    {{-- CARRUSEL / HERO (imágenes desde la tabla de configuración)  --}}
    @if ($configuracion->get('SITIO_HABILITAR_CARRUSEL', '1') && $sliders->isNotEmpty())
        <div id="sliderInicio" class="carousel slide carousel-fade hero-carousel" data-bs-ride="carousel" data-bs-interval="5500">
            <div class="carousel-indicators">
                @foreach ($sliders as $i => $slider)
                    <button type="button" data-bs-target="#sliderInicio" data-bs-slide-to="{{ $i }}"
                            class="{{ $loop->first ? 'active' : '' }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"
                            aria-label="Diapositiva {{ $i + 1 }}"></button>
                @endforeach
            </div>
            <div class="carousel-inner">
                @foreach ($sliders as $slider)
                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                        @if (data_get($slider, 'imagen'))
                            <img src="{{ $slider['imagen'] }}" class="d-block w-100 carousel-img" alt="{{ data_get($slider, 'titulo') }}" onerror="reemplazarImagenRota(this)">
                        @else
                            <div class="carousel-img d-flex align-items-center justify-content-center hero">
                                <div class="display-1">🍔</div>
                            </div>
                        @endif
                        <div class="carousel-caption carousel-caption-modern d-none d-md-block text-start">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-7">
                                        @if (data_get($slider, 'titulo'))
                                            <span class="badcillo text-uppercase mb-2">Bienvenido a {{ $configuracion->get('SITIO_NOMBRE', 'Burguer Willy') }}</span>
                                            <h1 class="display-4 fw-bolder mb-3 text-shadow">{{ $slider['titulo'] }}</h1>
                                        @endif
                                        @if (data_get($slider, 'subtitulo'))
                                            <p class="lead mb-4 text-shadow-soft">{{ $slider['subtitulo'] }}</p>
                                        @endif
                                        @if (data_get($slider, 'boton_texto'))
                                            <a href="{{ data_get($slider, 'boton_url', '#menu') }}" class="btn btn-brand btn-lg px-4 shadow">{{ $slider['boton_texto'] }}</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#sliderInicio" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#sliderInicio" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Siguiente</span>
            </button>
        </div>
    @else
        <header class="hero py-5">
            <div class="container py-4 text-center">
                <h1 class="display-4 fw-bolder mb-3">{{ $configuracion->get('SITIO_NOMBRE', 'Burger Willy and Grill') }}</h1>
                <p class="lead mb-4">{{ $configuracion->get('SITIO_ESLOGAN', 'Sabores que se quedan') }}</p>
                <a href="#menu" class="btn btn-brand btn-lg px-4">Ver menú</a>
            </div>
        </header>
    @endif

    {{-- CATEGORÍAS (píldoras llamativas)  --}}
    @if ($configuracion->get('SITIO_HABILITAR_MENU', '1'))
        <section id="categorias" class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="badcillo text-uppercase">Nuestro menú</span>
                    <h2 class="display-6 fw-bolder seccion-titulo">Explora por categoría</h2>
                    <p class="text-muted">Tocá una categoría para ver los productos de cada sucursal.</p>
                </div>

                @if ($categorias->isEmpty())
                    <div class="alert alert-warning text-center">No hay categorías disponibles por el momento.</div>
                @else
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-5" id="filtroCategorias">
                        <button type="button" class="btn btn-brand categoria-pill active" data-categoria="*">
                            <strong>Todos</strong>
                        </button>
                        @foreach ($categorias as $categoria)
                            <button type="button" class="btn btn-outline-brand categoria-pill"
                                    data-categoria="{{ $categoria->nombre }}">
                                <span class="categoria-emoji">🍽️</span>
                                <strong>{{ $categoria->nombre }}</strong>
                                <span class="badge text-bg-brand ms-1">{{ $categoria->productos_count }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- MENÚ / PRODUCTOS POR SUCURSAL  --}}
    @if ($configuracion->get('SITIO_HABILITAR_MENU', '1'))
        <section id="menu" class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="badcillo text-uppercase">Productos y precios</span>
                    <h2 class="display-6 fw-bolder seccion-titulo">Menú por sucursal
                        <span id="categoriaActual" class="badge text-bg-brand fs-6 align-middle ms-2">Todos</span>
                    </h2>
                    <p class="text-muted">Elige tu sucursal y revisa los productos disponibles con sus precios.</p>
                </div>

                @if ($sucursales->isEmpty())
                    <div class="alert alert-warning text-center">No hay sucursales disponibles por el momento.</div>
                @else
                    <ul class="nav nav-pills justify-content-center mb-4 flex-wrap gap-2" id="sucursalTab" role="tablist">
                        @foreach ($sucursales as $i => $sucursal)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link sucursal-tab {{ $loop->first ? 'active' : '' }}"
                                        id="sucursal-{{ $i }}-tab"
                                        data-bs-toggle="pill"
                                        data-bs-target="#sucursal-{{ $i }}"
                                        type="button" role="tab"
                                        aria-controls="sucursal-{{ $i }}"
                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    📍 {{ $sucursal->nombre }}
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content" id="sucursalTabContent">
                        @foreach ($sucursales as $i => $sucursal)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="sucursal-{{ $i }}" role="tabpanel" aria-labelledby="sucursal-{{ $i }}-tab">
                                @php
                                    $porCategoria = $sucursal->productosSucursal
                                        ->filter(fn ($sp) => $sp->disponibilidad && isset($sp->producto))
                                        ->groupBy(fn ($sp) => $sp->producto->categoria?->nombre ?? 'Otros');
                                @endphp

                                @if ($porCategoria->isEmpty())
                                    <div class="alert alert-light text-center">La sucursal <b>{{$sucursal->nombre}}</b> no tiene productos disponibles.</div>
                                @endif

                                <div class="alert alert-light text-center d-none" data-sin-resultados>No hay productos de la categoría seleccionada en esta sucursal.</div>

                                @foreach ($porCategoria as $categoria => $items)
                                    <div class="mb-4 bloque-categoria" data-categoria="{{ $categoria }}">
                                        <div class="d-flex align-items-center gap-2 mb-3">
                                            <span class="badge text-bg-brand px-3 py-2">{{ $categoria }}</span>
                                            <div class="flex-grow-1"><hr class="border-brand subtle my-0"></div>
                                        </div>
                                        <div class="row g-4">
                                            @foreach ($items as $sp)
                                                <div class="col-12 col-sm-6 col-lg-4 col-xl-3" data-categoria-item="{{ $categoria }}">
                                                    <div class="card tarjeta-producto h-100">
                                                        @if ($sp->producto->photo)
                                                            <img src="{{ asset('storage/'.$sp->producto->photo) }}" class="card-img-top object-fit-cover" alt="{{ $sp->producto->nombre }}">
                                                        @else
                                                            <div class="card-img-top bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center display-4">🍽️</div>
                                                        @endif
                                                        <div class="card-body d-flex flex-column">
                                                            <h6 class="card-title fw-bold">{{ $sp->producto->nombre }}</h6>
                                                            <p class="card-text text-muted small flex-grow-1">{{ $sp->producto->descripcion }}</p>
                                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                                <span class="precio h5 mb-0">Bs. {{ number_format($sp->precioproducto, 2) }}</span>
                                                                <span class="badge {{ $sp->disponibilidad ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                                    {{ $sp->disponibilidad ? 'Disponible' : 'Agotado' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- PROMOCIONES: seccion comentada temporalmente mientras se reconstruye el modulo.
    @if ($configuracion->get('SITIO_HABILITAR_PROMOCIONES', '1'))
        <section id="promociones" class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="badcillo text-uppercase">Ofertas vigentes</span>
                    <h2 class="display-6 fw-bolder seccion-titulo">Promociones por sucursal</h2>
                    <p class="text-muted">Descuentos que puedes aprovechar hoy en cada local.</p>
                </div>

                @if ($promocionesPorSucursal->isEmpty())
                    <div class="alert alert-light text-center">No hay promociones activas por el momento.</div>
                @else
                    <div class="row g-4">
                        @foreach ($promocionesPorSucursal as $idSucursal => $promos)
                            @php
                                $sucursalPromo = $promos->first()?->sucursalesProducto
                                    ->first(fn ($sp) => $sp->sucursal?->id == $idSucursal)?->sucursal;
                            @endphp
                            <div class="col-12 col-xl-6">
                                <div class="card tarjeta-producto h-100 p-2">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h5 class="card-title fw-bold mb-0">📍 {{ $sucursalPromo?->nombre ?? 'Sucursal' }}</h5>
                                            <span class="badge text-bg-danger">Ofertas activas</span>
                                        </div>
                                        <div class="row g-3">
                                            @foreach ($promos as $promo)
                                                <div class="col-12">
                                                    <div class="promo-item rounded-4 p-3 h-100">
                                                        @if ($promo->photo)
                                                            <img src="{{ asset('storage/'.$promo->photo) }}"
                                                                 class="w-100 mb-3"
                                                                 style="max-height: 220px; object-fit: cover; border-radius: 1rem;"
                                                                 alt="Promoción {{ $promo->nombre }}">
                                                        @endif
                                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                                            <div>
                                                                <h6 class="fw-bold mb-1">
                                                                    {{ $promo->nombre }}
                                                                    @if ($promo->tipodescuento === 'PORCENTAJE')
                                                                        <span class="badge text-bg-danger ms-1">-{{ number_format($promo->valordescuento, 0) }}%</span>
                                                                    @else
                                                                        <span class="badge text-bg-danger ms-1">-Bs. {{ number_format($promo->valordescuento, 2) }}</span>
                                                                    @endif
                                                                </h6>
                                                                <p class="small text-muted mb-2">
                                                                    Desde <strong>{{ $promo->fechainicio->format('d/m/Y') }}</strong>
                                                                    hasta <strong>{{ $promo->fechafin->format('d/m/Y') }}</strong>
                                                                </p>
                                                            </div>
                                                        </div>
                                                        @if ($promo->sucursalesProducto->isNotEmpty())
                                                            <ul class="list-unstyled small mb-0 row row-cols-md-2 gx-3">
                                                                @foreach ($promo->sucursalesProducto as $sp)
                                                                    @if ($sp->sucursal?->id == $idSucursal)
                                                                        <li class="col d-flex justify-content-between border-bottom py-1">
                                                                            <span>🍔 {{ $sp->producto?->nombre }}</span>
                                                                            <span class="text-muted">Bs. {{ number_format($sp->precioproducto, 2) }}</span>
                                                                        </li>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif
    --}}

    {{-- SUCURSALES Y HORARIOS --}}
    @if ($configuracion->get('SITIO_HABILITAR_SUCURSALES', '1'))
        <section id="sucursales" class="py-5 bg-light">
            <div class="container">
                <div class="text-center mb-5">
                    <span class="badcillo text-uppercase">Visítanos</span>
                    <h2 class="display-6 fw-bolder seccion-titulo">Sucursales y horarios de atención</h2>
                    <p class="text-muted">Encuentra tu sucursal más cercana y visita nuestros horarios.</p>
                </div>

                @if ($sucursales->isEmpty())
                    <div class="alert alert-warning text-center">No hay sucursales disponibles por el momento.</div>
                @else
                    <div class="row g-4 {{ $sucursales->count() === 1 ? 'justify-content-center' : '' }}">
                        @foreach ($sucursales as $sucursal)
                            <div class="col-12 {{ $sucursales->count() === 1 ? 'col-lg-8 col-xl-7' : 'col-lg-6' }}">
                                <div class="card tarjeta-producto h-100">
                                    <div id="mini-mapa-{{ $sucursal->id }}" class="sucursal-mapa {{ $sucursales->count() === 1 ? 'sucursal-mapa-unica' : '' }}"
                                         data-lat="{{ number_format((float) $sucursal->latitud, 6, '.', '') }}"
                                         data-lng="{{ number_format((float) $sucursal->longitud, 6, '.', '') }}">
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title fw-bold">{{ $sucursal->nombre }}</h5>
                                        <p class="card-text small text-muted mb-1">📍 {{ $sucursal->direccion }}</p>
                                        <p class="card-text small text-muted mb-3">📞 {{ $sucursal->telefono }}</p>

                                        <h6 class="text-uppercase text-muted small fw-bold mb-2">Horario de atención</h6>
                                        @php
                                            $horarios = $sucursal->horarios
                                                ->filter(fn ($h) => $h->activo)
                                                ->sortBy('diasemana');
                                        @endphp
                                        @if ($horarios->isEmpty())
                                            <p class="small text-muted mb-0">Horario por definir.</p>
                                        @else
                                            <div class="row row-cols-2 gx-3 gy-1 small">
                                                @foreach ($horarios as $h)
                                                    <div class="col d-flex justify-content-between border-bottom py-1">
                                                        <span class="horario-dia">{{ App\Models\HorarioSucursal::nombreDia($h->diasemana) }}</span>
                                                        <span class="text-muted">{{ substr($h->horaapertura, 0, 5) }} – {{ substr($h->horacierre, 0, 5) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif
@endsection

@push('styles')
    {{-- Leaflet para los mapas de sucursales --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        .badcillo {
            display: inline-block;
            color: var(--brand);
            font-weight: 700;
            letter-spacing: 2px;
            font-size: .85rem;
        }
        .hero-carousel .carousel-img {
            height: 78vh;
            object-fit: cover;
            filter: brightness(.55);
            min-height: 420px;
        }
        .carousel-caption-modern {
            right: auto;
            left: 0;
            top: 0;
            bottom: 0;
            display: flex !important;
            flex-direction: column;
            justify-content: center;
            width: 100%;
            padding: 0;
            text-align: left;
        }
        .text-shadow {
            text-shadow: 0 2px 12px rgba(0, 0, 0, .65);
        }
        .text-shadow-soft {
            text-shadow: 0 1px 6px rgba(0, 0, 0, .5);
        }
        .categoria-pill {
            border-radius: 2rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .categoria-pill:hover {
            transform: translateY(-3px);
            box-shadow: 0 .5rem 1.5rem color-mix(in srgb, var(--brand) 20%, transparent);
        }
        .categoria-emoji {
            font-size: 1.25rem;
        }
        .sucursal-tab {
            border-radius: 2rem;
        }
        .sucursal-tab.active {
            background-color: var(--brand) !important;
        }
        .border-brand {
            border-color: color-mix(in srgb, var(--brand) 25%, transparent) !important;
        }
        .promo-item {
            background: linear-gradient(135deg, #fff7ec 0%, #fdf1e0 100%);
            border: 1px solid rgba(212, 163, 115, .35);
        }
        .sucursal-mapa {
            height: 180px;
            border-radius: 1rem 1rem 0 0;
            background: #eee;
        }
        .sucursal-mapa-unica {
            height: 260px;
        }
        .categoria-pill.active {
            pointer-events: auto;
        }
        @media (max-width: 767px) {
            .hero-carousel .carousel-img {
                height: 62vh;
                min-height: 340px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="module">
        window.reemplazarImagenRota = function(img) {
            if (!img || img.dataset.reemplazado) {
                return;
            }
            img.dataset.reemplazado = '1';

            const $contenedor = $(img).parent();
            if ($contenedor.length === 0) {
                return;
            }

            $contenedor.html(
                '<div class="carousel-img d-flex align-items-center justify-content-center hero">' +
                '<div class="display-1">🍔</div>' +
                '</div>'
            );
        };

        $(document).ready(function() {
            const $botones = $('#filtroCategorias .categoria-pill');
            const $etiquetaActual = $('#categoriaActual');

            function aplicarFiltro(categoria) {
                $('.bloque-categoria').each(function() {
                    const $bloque = $(this);
                    const mostrar = categoria === '*' || $bloque.data('categoria') === categoria;
                    $bloque.toggle(mostrar);
                });

                $('.tab-pane').each(function() {
                    const $pane = $(this);
                    const bloquesVisibles = $pane.find('.bloque-categoria:visible');
                    const $vacio = $pane.find('[data-sin-resultados]');
                    if ($vacio.length) {
                        $vacio.toggleClass('d-none', bloquesVisibles.length > 0);
                    }
                });

                if ($etiquetaActual.length) {
                    $etiquetaActual.text(categoria === '*' ? 'Todos' : categoria);
                }

                const $menu = $('#menu');
                if ($menu.length) {
                    $menu[0].scrollIntoView({ behavior: 'smooth' });
                }
            }

            $botones.on('click', function() {
                const $btn = $(this);
                const categoria = $btn.data('categoria');

                $botones.removeClass('active btn-brand').addClass('btn-outline-brand');
                $btn.addClass('active btn-brand').removeClass('btn-outline-brand');

                aplicarFiltro(categoria);
            });

            $('[data-bs-toggle="pill"]').on('shown.bs.tab', function() {
                const $activo = $('#filtroCategorias .categoria-pill.active');
                aplicarFiltro($activo.length ? $activo.data('categoria') : '*');
            });

            $('[id^="mini-mapa-"]').each(function() {
                const $contenedor = $(this);
                const id = $contenedor.attr('id');
                const lat = parseFloat($contenedor.data('lat'));
                const lng = parseFloat($contenedor.data('lng'));

                const mapa = L.map(id, { scrollWheelZoom: false }).setView([lat, lng], 15);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(mapa);
                L.marker([lat, lng]).addTo(mapa);
            });
        });
    </script>
@endpush