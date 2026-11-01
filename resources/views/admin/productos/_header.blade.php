
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Catálogo de productos</h1>
        <p class="text-muted mb-0">Crea y edita los productos de la empresa y agrúpalos por categoría.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.productos-sucursal.index') }}" class="btn btn-outline-brand">🏪 Precios por sucursal</a>
        <button type="button" class="btn btn-outline-brand"
                data-bs-toggle="modal"
                data-bs-target="{{ $puedeCrearCategoria ? '#nuevaCategoriaModal' : '#sinPermisoModal' }}"
                @if (! $puedeCrearCategoria) data-mensaje="No tienes permiso para crear categorías. Solicita el permiso <strong>300</strong>." @endif>
            + Categoría
        </button>
        <button type="button" class="btn btn-brand"
                data-bs-toggle="modal"
                data-bs-target="{{ $puedeCrearProducto ? '#nuevoProductoModal' : '#sinPermisoModal' }}"
                @if (! $puedeCrearProducto) data-mensaje="No tienes permiso para crear productos. Solicita el permiso <strong>310</strong>." @endif>
            + Producto
        </button>
    </div>
</div>

<div class="alert alert-light border small mb-4">
    <strong>¿Cómo funciona?</strong>
    Primero crea las <strong>categorías</strong> (por ejemplo: Hamburguesas, Porciones, Gaseosas).
    Luego crea los <strong>productos</strong> y selecciona la categoría que le corresponde.
    El precio de venta no se define aquí: cada sucursal lo fija en
    <a href="{{ route('dashboard.productos-sucursal.index') }}" class="alert-link">Productos por sucursal</a>.
</div>
