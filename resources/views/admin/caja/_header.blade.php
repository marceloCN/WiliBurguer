<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h3 fw-bold mb-1">Caja</h1>
        <p class="text-muted mb-0">Apertura y cierre de jornada, movimientos e historial de caja.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if ($miTurno)
            <span class="badge text-bg-success">💰 Caja abierta · {{ $miTurno->caja?->nombre }}</span>
        @else
            <span class="badge text-bg-secondary">💰 Caja cerrada</span>
        @endif
        <span class="badge text-bg-brand">🧑‍💼 {{ $empleado->nombre }} {{ $empleado->apellido }}</span>
    </div>
</div>

@if ($sucursales->count() > 1)
    <div class="card tarjeta-producto mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span class="fw-bold">Sucursal</span>
            <span class="badge text-bg-dark">{{ $sucursales->count() }} disponibles</span>
        </div>
        <div class="card-body d-flex flex-wrap gap-2">
            @foreach ($sucursales as $sucursal)
                <a href="{{ route('dashboard.caja.index', ['idsucursal' => $sucursal->id]) }}"
                   class="btn btn-sm {{ $sucursalActual->id === $sucursal->id ? 'btn-brand' : 'btn-outline-secondary' }}">
                    📍 {{ $sucursal->nombre }}
                </a>
            @endforeach
        </div>
    </div>
@else
    <div class="d-flex align-items-center gap-2 mb-4">
        <span class="badge text-bg-brand">{{ $sucursalActual->nombre }}</span>
        <span class="text-muted small">Caja de tu sucursal</span>
    </div>
@endif
