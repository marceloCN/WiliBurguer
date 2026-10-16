<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Insumos y unidades de medida</h1>
        <p class="text-muted mb-0">
            Define los insumos que usarán las sucursales y las unidades de medida para controlar el inventario.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#ayudaModal">
            Ayuda
        </button>
        <a href="{{ route('dashboard.insumos.ayuda.pdf') }}" class="btn btn-outline-brand">
            Guía PDF
        </a>
        <button type="button" class="btn btn-outline-brand"
                data-bs-toggle="modal"
                data-bs-target="{{ $puedeCrearUnidad ? '#nuevaUnidadModal' : '#sinPermisoModal' }}"
                @if (! $puedeCrearUnidad) data-mensaje="No tienes permiso para crear unidades de medida. Solicita el permiso <strong>330</strong>." @endif>
            + Unidad
        </button>
        <button type="button" class="btn btn-brand"
                data-bs-toggle="modal"
                data-bs-target="{{ $puedeCrearInsumo ? '#nuevoInsumoModal' : '#sinPermisoModal' }}"
                @if (! $puedeCrearInsumo) data-mensaje="No tienes permiso para crear insumos. Solicita el permiso <strong>336</strong>." @endif>
            + Insumo
        </button>
    </div>
</div>

<div class="alert alert-light border small mb-4">
    <strong>¿Cómo funciona?</strong>
    Primero crea las <strong>unidades de medida</strong> (por ejemplo: kg, litro, unidad).
    Luego crea los <strong>insumos</strong> y selecciona la unidad que le corresponde.
    Los insumos activos aparecerán disponibles en el inventario de cada sucursal.
</div>
