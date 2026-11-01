<div class="modal fade" id="asignarProductoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('dashboard.productos-sucursal.store') }}"
                  data-productos-disponibles='@json($productosDisponibles->values())'>
                @csrf
                <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Asignar producto a {{ $sucursalActual->nombre }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="asignarCategoria" class="form-label">Categoría *</label>
                            <select id="asignarCategoria" class="form-select" required>
                                <option value="" disabled selected>Selecciona una categoría…</option>
                                @foreach ($categorias as $categoria)
                                    @if ($productosDisponibles->contains('idcategoria', $categoria->id))
                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="form-text">
                                Solo se muestran las categorías con productos aún no asignados a esta sucursal.
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="asignarProducto" class="form-label">Producto *</label>
                            <select name="idproducto" id="asignarProducto" class="form-select" required disabled>
                                <option value="" disabled selected>Primero elige una categoría…</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="asignarPrecio" class="form-label">Precio de venta en esta sucursal (Bs.) *</label>
                            <input type="number" name="precioproducto" id="asignarPrecio" class="form-control"
                                   min="0" step="0.01" required value="{{ old('precioproducto') }}" placeholder="Ej.: 35.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-brand">Asignar producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarPrecioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            @if ($puedeEditarPrecio)
                <form method="POST" id="form-editar-precio" action="{{ route('dashboard.productos-sucursal.precio', 'ID') }}"
                      data-precio-url-template="{{ route('dashboard.productos-sucursal.precio', 'ID') }}">
                    @csrf
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Editar precio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            Producto: <strong id="editarPrecioProducto"></strong><br>
                            Sucursal: <span id="editarPrecioSucursal"></span>
                        </p>
                        <label for="editarPrecioValor" class="form-label">Nuevo precio de venta (Bs.) *</label>
                        <input type="number" name="precioproducto" id="editarPrecioValor" class="form-control"
                               min="0" step="0.01" required>
                        <div class="form-text">
                            El cambio queda registrado en el historial de precios de la sucursal.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-brand">Actualizar precio</button>
                    </div>
                </form>
            @else
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">Editar precio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-0">
                        <strong>Sin permiso.</strong>
                        No tienes autorización para editar precios de productos.
                        Solicita al administrador el permiso <strong>318</strong> si necesitas realizar esta acción.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="detalleProductoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Detalle del producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <img id="detalleProductoFoto" src="" alt="Foto del producto"
                         class="rounded border object-fit-cover bg-light d-none"
                         style="width: 120px; height: 120px;">
                    <div id="detalleProductoSinFoto" class="d-flex align-items-center justify-content-center rounded border bg-light mx-auto"
                         style="width: 120px; height: 120px; font-size: 3rem;">
                        🍔
                    </div>
                </div>
                <h5 id="detalleProductoNombre" class="fw-bold text-center mb-2"></h5>
                <div class="text-center mb-3">
                    <span id="detalleProductoCategoria" class="badge text-bg-light border"></span>
                    <span id="detalleProductoDisponibilidad" class="badge"></span>
                </div>
                <p id="detalleProductoDescripcion" class="text-muted mb-3"></p>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <span class="text-muted">Precio en sucursal</span>
                    <span id="detalleProductoPrecio" class="fw-bold h5 mb-0 text-brand"></span>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sinProductosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Sin productos por asignar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-0">
                    Todos los productos activos del catálogo ya están asignados a esta sucursal.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
