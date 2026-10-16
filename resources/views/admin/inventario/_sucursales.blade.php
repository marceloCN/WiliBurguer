@if ($esAdmin)
    <div class="col-12 col-lg-4">
        <div class="card tarjeta-producto h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold">Sucursales</span>
                <span class="badge text-bg-dark">{{ $sucursales->count() }}</span>
            </div>
            @if ($sucursales->count() <= 1)
                <div class="card-body">
                    <div class="fw-semibold">{{ $sucursalActual->nombre }}</div>
                    @if ($empleado->sucursal && $sucursalActual->id === $empleado->sucursal->id)
                        <div class="small text-muted">Tu sucursal asignada</div>
                    @endif
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($sucursales as $sucursal)
                        <a href="{{ route('dashboard.inventario.index', ['idsucursal' => $sucursal->id]) }}"
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                                  {{ $sucursalActual->id === $sucursal->id ? 'active' : '' }}">
                            <span class="fw-semibold">{{ $sucursal->nombre }}</span>
                            @if (! $sucursal->estado)
                                <span class="badge rounded-pill text-bg-secondary">Inactiva</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
