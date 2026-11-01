@if ($conSelectorSucursal)
    <div class="col-12 col-lg-4">
        <div class="card tarjeta-producto h-100">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold">Sucursales</span>
                <span class="badge text-bg-dark">{{ $sucursales->count() }}</span>
            </div>
            <div class="list-group list-group-flush">
                @foreach ($sucursales as $sucursal)
                    <a href="{{ route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursal->id]) }}"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                              {{ $sucursalActual->id === $sucursal->id ? 'active' : '' }}">
                        <span class="fw-semibold">{{ $sucursal->nombre }}</span>
                        @if ($empleado->sucursal && $sucursal->id === $empleado->sucursal->id)
                            <span class="badge rounded-pill {{ $sucursalActual->id === $sucursal->id ? 'text-bg-light' : 'text-bg-brand' }}">
                                Tu sucursal
                            </span>
                        @elseif (! $sucursal->estado)
                            <span class="badge rounded-pill text-bg-secondary">Inactiva</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
