<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Productos por sucursal</h1>
        <p class="text-muted mb-0">Define qué productos vende cada sucursal, a qué precio y con qué disponibilidad.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('dashboard.recetas.index') }}" class="btn btn-outline-brand">📖 Recetas</a>
        @if ($puedeRegistrar)
            <button type="button" class="btn btn-brand" data-bs-toggle="modal"
                    data-bs-target="{{ $productosDisponibles->isEmpty() ? '#sinProductosModal' : '#asignarProductoModal' }}">
                + Asignar producto
            </button>
        @else
            <button type="button" class="btn btn-brand"
                    data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                    data-mensaje="No tienes permiso para asignar productos a la sucursal. Solicita el permiso <strong>316</strong>.">
                + Asignar producto
            </button>
        @endif
    </div>
</div>

<div class="alert alert-light border small mb-4">
    Cada producto del catálogo se vende en la sucursal con su propio <strong>precio</strong>:
    <span class="badge text-bg-success">Disponible</span> aparece en el menú de la sucursal y
    <span class="badge text-bg-secondary">No disponible</span> queda oculto sin perder su historial de precios.
</div>
