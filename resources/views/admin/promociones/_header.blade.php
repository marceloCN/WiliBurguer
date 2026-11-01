<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Promociones</h1>
        <p class="text-muted mb-0">Publica ofertas por sucursal con sus días, vigencia y productos participantes.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursalActual->id]) }}" class="btn btn-outline-brand">
            🏷️ Precios por sucursal
        </a>
        <button type="button" class="btn btn-outline-secondary"
                data-bs-toggle="modal" data-bs-target="#reporteModal">
            📄 Reporte PDF
        </button>
        <button type="button" class="btn btn-brand"
                data-bs-toggle="modal"
                data-bs-target="{{ $puedeCrear ? '#promocionModal' : '#sinPermisoModal' }}"
                data-modo="crear"
                @if (! $puedeCrear) data-mensaje="No tienes permiso para crear promociones. Solicita el permiso <strong>350</strong>." @endif>
            + Promoción
        </button>
    </div>
</div>

<div class="alert alert-light border small mb-4">
    <strong>¿Cómo funciona?</strong>
    Una promoción se crea con su nombre, foto, tipo de descuento y vigencia, y se publica en
    <strong>una sucursal</strong>. Después eliges en qué días de la semana aplica y qué productos entran,
    con la cantidad de cada uno.
    Para que salga en la portada debe estar <strong>activa</strong>, dentro de su rango de fechas y con el día de hoy incluido.
</div>
