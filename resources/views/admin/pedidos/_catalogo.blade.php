<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
        <span class="fw-bold">Catálogo · {{ $sucursalActual->nombre }}</span>
        <span class="badge text-bg-dark">{{ $catalogo->sum(fn ($item) => $item['productos']->count()) }} producto(s)</span>
    </div>

    <div class="card-body">
        @if ($catalogo->isEmpty())
            <div class="alert alert-light text-center mb-0">
                Esta sucursal no tiene productos disponibles a la venta.
            </div>
        @else
            @unless ($aperturaCaja)
                <div class="alert alert-warning py-2 small mb-3">
                    Abre la caja del turno para agregar productos al pedido.
                </div>
            @endunless

            <div class="input-group mb-3">
                <span class="input-group-text">🔍</span>
                <input type="search" id="busqueda-productos" class="form-control"
                       placeholder="Buscar producto..." aria-label="Buscar producto">
            </div>

            <div class="d-flex flex-nowrap gap-2 pb-2 mb-3 overflow-auto">
                <button type="button" class="btn btn-sm btn-brand active categoria-filtro flex-shrink-0" data-categoria="">
                    Todos
                </button>
                @if ($promociones->isNotEmpty())
                    <button type="button" class="btn btn-sm btn-outline-secondary categoria-filtro flex-shrink-0"
                            data-categoria="promociones">
                        🔥 Promociones
                    </button>
                @endif
                @foreach ($catalogo as $item)
                    <button type="button" class="btn btn-sm btn-outline-secondary categoria-filtro flex-shrink-0"
                            data-categoria="{{ $item['categoria']->nombre }}">
                        {{ $item['categoria']->nombre }}
                    </button>
                @endforeach
            </div>

            @include('admin.pedidos._promociones')

            @foreach ($catalogo as $item)
                <div class="mb-4 bloque-categoria" data-categoria="{{ $item['categoria']->nombre }}">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge text-bg-brand px-3 py-2">{{ $item['categoria']->nombre }}</span>
                        <div class="flex-grow-1"><hr class="border-brand subtle my-0"></div>
                    </div>

                    <div class="row g-3">
                        @foreach ($item['productos'] as $sp)
                            <div class="col-12 col-sm-6 col-lg-4 col-xl-6 producto-item"
                                 data-nombre="{{ $sp->producto->nombre }}">
                                <div class="card tarjeta-producto h-100">
                                    @if ($sp->producto->photo)
                                        <img src="{{ asset('storage/'.$sp->producto->photo) }}"
                                             class="card-img-top object-fit-cover" style="max-height: 140px;"
                                             alt="{{ $sp->producto->nombre }}"
                                             onerror="this.onerror=null;this.src='{{ asset('images/placeholder-producto.svg') }}'">
                                    @else
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                             style="max-height: 140px; min-height: 100px;">
                                            <span class="text-muted">🍽️ Sin imagen</span>
                                        </div>
                                    @endif
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title fw-bold mb-1">{{ $sp->producto->nombre }}</h6>
                                        <p class="small text-muted flex-grow-1 mb-2 line-clamp-2">{{ $sp->producto->descripcion }}</p>

                                        @php
                                            $promosProducto = $sp->promocionesSucursal
                                                ->filter(fn ($promo) => $promociones->contains('id', $promo->id));
                                        @endphp

                                        @if ($promosProducto->isNotEmpty())
                                            @foreach ($promosProducto as $promo)
                                                <span class="badge text-bg-danger mb-1 align-self-start">
                                                    🔥 {{ $promo->promocion?->nombre }}
                                                </span>
                                            @endforeach
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center mt-2 gap-2">
                                            <span class="precio h5 mb-0 text-nowrap">Bs. {{ number_format($sp->precioproducto, 2) }}</span>
                                            <button type="button"
                                                    class="btn btn-sm btn-brand agregar-carrito flex-shrink-0"
                                                    data-id="{{ $sp->id }}"
                                                    data-nombre="{{ $sp->producto->nombre }}"
                                                    data-precio="{{ $sp->precioproducto }}"
                                                    @disabled(! $aperturaCaja)>
                                                + Agregar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
