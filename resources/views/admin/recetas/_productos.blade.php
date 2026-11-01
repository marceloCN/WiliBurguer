<div class="card tarjeta-producto">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Productos</span>
        <span class="badge text-bg-brand">{{ $productos->total() }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.recetas.index') }}" class="mb-4">
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
                <a href="{{ route('dashboard.recetas.index') }}" class="small d-inline-block mt-2">Limpiar filtros</a>
            @endif
        </form>

        @if ($productos->isEmpty())
            <div class="alert alert-light border text-center mb-0">
                @if ($busqueda !== '' || $idcategoria > 0)
                    Ningún producto coincide con los filtros.
                @else
                    Aún no hay productos registrados.
                @endif
            </div>
        @else
            <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 g-3">
                @foreach ($productos as $producto)
                    @php
                        $tieneReceta = $producto->detallesReceta->isNotEmpty();
                        $tieneFoto = filled($producto->photo);
                    @endphp
                    <div class="col">
                        <div class="card tarjeta-producto h-100">
                            <div class="position-relative bg-light border-bottom" style="height: 110px;">
                                @if ($tieneFoto)
                                    <img src="{{ asset('storage/'.$producto->photo) }}"
                                         class="w-100 h-100 object-fit-cover rounded-top"
                                         alt="{{ $producto->nombre }}">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                        <span style="font-size: 2.5rem;">🍔</span>
                                    </div>
                                @endif
                                @unless ($producto->estado)
                                    <span class="position-absolute top-0 end-0 m-2 badge text-bg-secondary">Inactivo</span>
                                @endunless
                            </div>

                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="card-title fw-bold mb-1 text-truncate" title="{{ $producto->nombre }}">
                                    {{ $producto->nombre }}
                                </h6>
                                <span class="badge text-bg-light border align-self-start mb-2">
                                    {{ $producto->categoria?->nombre ?? 'Sin categoría' }}
                                </span>

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    @if ($tieneReceta)
                                        <span class="badge text-bg-success">{{ $producto->detallesReceta->count() }} insumo(s)</span>
                                    @else
                                        <span class="badge text-bg-warning">Sin receta</span>
                                    @endif
                                </div>

                                <button type="button"
                                        class="btn btn-sm {{ $tieneReceta ? 'btn-outline-brand' : 'btn-outline-warning' }} w-100 mt-auto abrir-panel-receta"
                                        data-producto="{{ $producto->id }}">
                                    {{ $tieneReceta ? 'Ver receta' : 'Crear receta' }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @if ($productos->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }}
                    de {{ $productos->total() }} productos
                </span>
                <nav aria-label="Paginación de productos">
                    {{ $productos->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
