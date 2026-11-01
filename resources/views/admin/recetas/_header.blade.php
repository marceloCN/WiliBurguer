<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Recetas de productos</h1>
        <p class="text-muted mb-0">
            Vincula cada producto con los insumos y las cantidades que se necesitan para prepararlo.
        </p>
    </div>
    <div class="d-flex gap-2">
        @if ($puedeVerInsumos)
            <a href="{{ route('dashboard.insumos.index') }}" class="btn btn-outline-info">
                Catálogo de insumos
            </a>
        @endif
    </div>
</div>

<div class="alert alert-light border small mb-4">
    <strong>¿Cómo funciona?</strong>
    Busca el producto y pulsa <em>Ver receta</em> para abrirlo en el panel lateral.
    Allí agregas insumos, corregís cantidades y quitás los insumos que ya no usás.
    Solo aparecen los insumos que están activos en el catálogo de insumos.
</div>
