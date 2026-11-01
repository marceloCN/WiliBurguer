<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Productos · {{ $sucursalActual->nombre }}</span>
        <span class="badge text-bg-brand">{{ $productosSucursal->total() }} asignado(s)</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.productos-sucursal.index') }}" class="mb-4">
            <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">
            <div class="row g-2">
                <div class="col-12 col-md-6">
                    <input type="text" name="busqueda" class="form-control"
                           placeholder="Buscar producto por nombre…" value="{{ $busqueda }}">
                </div>
                <div class="col-12 col-md-4">
                    <select name="idcategoria" class="form-select">
                        <option value="0">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected($idcategoria === $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-outline-brand">Filtrar</button>
                </div>
            </div>
            @if ($busqueda !== '' || $idcategoria > 0)
                <a href="{{ route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursalActual->id]) }}"
                   class="small d-inline-block mt-2">Limpiar filtros</a>
            @endif
        </form>

        @if ($productosSucursal->isEmpty())
            <div class="alert alert-light border text-center mb-0">
                @if ($busqueda !== '' || $idcategoria > 0)
                    Ningún producto asignado coincide con los filtros.
                @else
                    Todavía no hay productos asignados a esta sucursal. Pulsa "+ Asignar producto" para comenzar.
                @endif
            </div>
        @else
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                @foreach ($productosSucursal as $productoSucursal)
                    @php
                        $producto = $productoSucursal->producto;
                        $tieneFoto = filled($producto?->photo);
                    @endphp
                    <div class="col">
                        <div class="card tarjeta-producto h-100">
                            <div class="position-relative bg-light border-bottom" style="height: 110px;">
                                @if ($tieneFoto)
                                    <img src="{{ asset('storage/'.$producto->photo) }}"
                                         class="w-100 h-100 object-fit-cover rounded-top"
                                         alt="{{ $producto?->nombre ?? 'Producto' }}">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                        <span style="font-size: 2.5rem;">🍔</span>
                                    </div>
                                @endif
                                @if ($productoSucursal->disponibilidad)
                                    <span class="position-absolute top-0 end-0 m-2 badge text-bg-success">Disponible</span>
                                @else
                                    <span class="position-absolute top-0 end-0 m-2 badge text-bg-secondary">No disponible</span>
                                @endif
                            </div>

                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="card-title fw-bold mb-1 text-truncate" title="{{ $producto?->nombre ?? '—' }}">
                                    {{ $producto?->nombre ?? '—' }}
                                </h6>
                                <span class="badge text-bg-light border align-self-start mb-2">
                                    {{ $producto?->categoria?->nombre ?? 'Sin categoría' }}
                                </span>

                                <div class="fw-semibold text-brand mb-2">
                                    Bs. {{ number_format((float) $productoSucursal->precioproducto, 2) }}
                                </div>

                                <div class="d-grid gap-2 mt-auto">
                                    <div class="d-flex gap-2">
                                        @if ($puedeEditarPrecio)
                                            <button type="button" class="btn btn-sm btn-outline-brand flex-fill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editarPrecioModal"
                                                    data-id="{{ $productoSucursal->id }}"
                                                    data-producto="{{ $producto?->nombre }}"
                                                    data-sucursal="{{ $sucursalActual->nombre }}"
                                                    data-precio="{{ number_format((float) $productoSucursal->precioproducto, 2, '.', '') }}">
                                                Precio
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-brand flex-fill"
                                                    data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                                    data-mensaje="No tienes permiso para editar precios. Solicita el permiso <strong>318</strong>.">
                                                Precio
                                            </button>
                                        @endif

                                        @if ($puedeDisponibilidad)
                                            <form method="POST"
                                                  action="{{ route('dashboard.productos-sucursal.disponibilidad', $productoSucursal) }}"
                                                  class="flex-fill">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                                    {{ $productoSucursal->disponibilidad ? 'Ocultar' : 'Mostrar' }}
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill"
                                                    data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                                    data-mensaje="No tienes permiso para cambiar la disponibilidad. Solicita el permiso <strong>319</strong>.">
                                                {{ $productoSucursal->disponibilidad ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        @endif
                                    </div>

                                    <div class="d-flex gap-2">
                                        @if ($puedeVerHistorial)
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill"
                                                    data-historial-url="{{ route('dashboard.productos-sucursal.historial', $productoSucursal) }}"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#historialSPModal">
                                                Historial
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary flex-fill"
                                                    data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                                    data-mensaje="No tienes permiso para ver el historial. Solicita el permiso <strong>320</strong>.">
                                                Historial
                                            </button>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-outline-info flex-fill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detalleProductoModal"
                                                data-nombre="{{ $producto?->nombre }}"
                                                data-categoria="{{ $producto?->categoria?->nombre ?? 'Sin categoría' }}"
                                                data-descripcion="{{ $producto?->descripcion ?? 'Sin descripción.' }}"
                                                data-precio="{{ number_format((float) $productoSucursal->precioproducto, 2) }}"
                                                data-disponibilidad="{{ $productoSucursal->disponibilidad ? 'Disponible' : 'No disponible' }}"
                                                data-photo="{{ $tieneFoto ? asset('storage/'.$producto->photo) : '' }}">
                                            Ver más
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if ($productosSucursal->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $productosSucursal->firstItem() }}–{{ $productosSucursal->lastItem() }}
                    de {{ $productosSucursal->total() }} producto(s)
                </span>
                <nav aria-label="Paginación de productos por sucursal">
                    {{ $productosSucursal->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
