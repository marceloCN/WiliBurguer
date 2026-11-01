<div class="card tarjeta-producto">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Productos</span>
        <span class="badge text-bg-brand">{{ $productos->total() }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.productos.index') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-12 col-md-7">
                    <input type="text" name="busqueda" class="form-control"
                           placeholder="Buscar producto por nombre…" value="{{ $busqueda }}">
                </div>
                <div class="col-12 col-md-3">
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
                <a href="{{ route('dashboard.productos.index') }}" class="small d-inline-block mt-2">Limpiar filtros</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 64px;">Foto</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr>
                            <td>
                                @if ($producto->photo)
                                    <img src="{{ asset('storage/'.$producto->photo) }}" alt="{{ $producto->nombre }}"
                                         class="rounded border object-fit-cover" width="48" height="48">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted"
                                          style="width: 48px; height: 48px;">🍔</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $producto->nombre }}</div>
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($producto->descripcion, 70) }}</div>
                            </td>
                            <td>
                                <span class="badge text-bg-light border">{{ $producto->categoria?->nombre ?? '—' }}</span>
                            </td>
                            <td>
                                @if ($producto->estado)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($puedeEditarProducto)
                                    <button type="button" class="btn btn-sm btn-outline-brand"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editarProductoModal"
                                            data-id="{{ $producto->id }}"
                                            data-nombre="{{ $producto->nombre }}"
                                            data-descripcion="{{ $producto->descripcion }}"
                                            data-categoria="{{ $producto->idcategoria }}"
                                            data-photo="{{ $producto->photo }}">
                                        Editar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-brand"
                                            data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                            data-mensaje="No tienes permiso para editar productos. Solicita el permiso <strong>311</strong>.">
                                        Editar
                                    </button>
                                @endif

                                @if ($puedeDesactivarProducto)
                                    <form method="POST" action="{{ route('dashboard.productos.estado', $producto) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            {{ $producto->estado ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                            data-mensaje="No tienes permiso para dar de baja productos. Solicita el permiso <strong>315</strong>.">
                                        {{ $producto->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                @if ($busqueda !== '' || $idcategoria > 0)
                                    Ningún producto coincide con los filtros.
                                @else
                                    No hay productos en el catálogo. Pulsa "+ Producto" para crear el primero.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($productos->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }}
                    de {{ $productos->total() }} producto(s)
                </span>
                <nav aria-label="Paginación de productos">
                    {{ $productos->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
