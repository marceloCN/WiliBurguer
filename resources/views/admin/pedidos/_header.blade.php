<div class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Generar pedido</h1>
        <p class="text-muted mb-0">Ventana de atención: toma pedidos de mesa y para llevar.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if ($aperturaCaja)
            <span class="badge text-bg-success">💰 Caja abierta · {{ $aperturaCaja->caja?->nombre }}</span>
        @else
            <span class="badge text-bg-secondary">💰 Caja cerrada</span>
        @endif
        <span class="badge text-bg-brand">🧑‍💼 {{ $empleado->nombre }} {{ $empleado->apellido }}</span>
    </div>
</div>

@if ($sucursales->count() > 1)
    <div class="card tarjeta-producto mb-4">
        <div class="card-header bg-light d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
            <span class="fw-bold">Sucursal</span>
            <span class="badge text-bg-dark">{{ $sucursales->count() }} disponibles</span>
        </div>
        <div class="card-body d-flex flex-wrap gap-2">
            @foreach ($sucursales as $sucursal)
                <a href="{{ route('dashboard.generar-pedidos.index', ['idsucursal' => $sucursal->id]) }}"
                   class="btn btn-sm {{ $sucursalActual->id === $sucursal->id ? 'btn-brand' : 'btn-outline-secondary' }}">
                    📍 {{ $sucursal->nombre }}
                </a>
            @endforeach
        </div>
    </div>
@else
    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="badge text-bg-brand">{{ $sucursalActual->nombre }}</span>
        <span class="text-muted small">Atendiendo en tu sucursal</span>
    </div>
@endif
