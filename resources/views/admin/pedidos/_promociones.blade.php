{{-- Promociones vigentes hoy, mostradas como una categoría más del catálogo --}}
@if ($promociones->isNotEmpty())
    <div class="mb-4 bloque-categoria" data-categoria="promociones">
        <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge text-bg-brand px-3 py-2">🔥 Promociones de hoy</span>
            <span class="badge text-bg-dark">{{ $promociones->count() }}</span>
            <div class="flex-grow-1"><hr class="border-brand subtle my-0"></div>
        </div>

        <div class="row g-3">
            @foreach ($promociones as $promo)
                @php
                    $subtotalCombo = $promo->subtotalCombo();
                    $totalCombo = $promo->totalCombo();
                    $sinProductos = $promo->productosSucursal->isEmpty();
                @endphp

                <div class="col-12 col-sm-6 col-lg-4 col-xl-6 producto-item"
                     data-nombre="{{ $promo->promocion?->nombre }}">
                    <div class="card tarjeta-producto h-100">
                        @if ($promo->promocion?->photo)
                            <img src="{{ asset('storage/'.$promo->promocion->photo) }}"
                                 class="card-img-top object-fit-cover" style="max-height: 140px;"
                                 alt="{{ $promo->promocion?->nombre }}"
                                 onerror="this.onerror=null;this.src='{{ asset('images/placeholder-producto.svg') }}'">
                        @else
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="max-height: 140px; min-height: 100px;">
                                <span class="text-muted">🔥 Sin imagen</span>
                            </div>
                        @endif
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <h6 class="card-title fw-bold mb-0">🔥 {{ $promo->promocion?->nombre }}</h6>
                                <span class="badge text-bg-danger text-nowrap">{{ $promo->etiquetaDescuento() }}</span>
                            </div>

                            <div class="small text-muted mb-2">
                                Válida hasta {{ $promo->fechafin->format('d/m/Y') }}
                            </div>

                            @if ($sinProductos)
                                <p class="small text-warning flex-grow-1 mb-2">
                                    ⚠️ Sin productos participantes; no se puede vender.
                                </p>
                            @else
                                <ul class="list-unstyled small flex-grow-1 mb-0">
                                    @foreach ($promo->productosSucursal as $sp)
                                        <li class="d-flex justify-content-between border-bottom py-1">
                                            <span>🍔 {{ $sp->producto?->nombre }}</span>
                                            <span class="text-muted">
                                                ×{{ $sp->pivot->cantidad_producto }}
                                                · Bs. {{ number_format((float) $sp->precioproducto * (int) $sp->pivot->cantidad_producto, 2) }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            {{-- Precio del combo y acción principal, como en las demás tarjetas --}}
                            <div class="d-flex justify-content-between align-items-center mt-3 gap-2 flex-wrap">
                                @unless ($sinProductos)
                                    <div class="text-nowrap">
                                        <div class="small text-muted">Combo completo</div>
                                        @if ($totalCombo < $subtotalCombo)
                                            <span class="small text-decoration-line-through text-muted me-1">
                                                Bs. {{ number_format($subtotalCombo, 2) }}
                                            </span>
                                        @endif
                                        <span class="precio h5 mb-0">Bs. {{ number_format($totalCombo, 2) }}</span>
                                    </div>
                                @endunless

                                <button type="button"
                                        class="btn btn-sm btn-outline-danger {{ $sinProductos ? 'w-100' : '' }} agregar-promo flex-shrink-0"
                                        data-id="{{ $promo->id }}"
                                        data-nombre="{{ $promo->promocion?->nombre }}"
                                        data-precio="{{ number_format($subtotalCombo, 2, '.', '') }}"
                                        data-descuento="{{ number_format($promo->descuentoCombo(), 2, '.', '') }}"
                                        @disabled(! $aperturaCaja || $sinProductos)>
                                    + Agregar combo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
