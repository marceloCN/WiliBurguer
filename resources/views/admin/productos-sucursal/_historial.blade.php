{{-- Historial de precios de un producto en su sucursal. Contenido del modal
     #historialSPModal. Requiere $historial, $productoSucursal y $sucursal. --}}
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <div class="fw-bold">{{ $productoSucursal->producto?->nombre ?? '—' }}</div>
        <span class="small text-muted">{{ $sucursal->nombre }}</span>
    </div>
    <span class="badge text-bg-dark">{{ $historial->count() }} cambio(s)</span>
</div>

@if ($historial->isEmpty())
    <div class="text-center text-muted py-4">Este producto aún no tiene cambios de precio registrados.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Fecha y hora</th>
                    <th class="text-end">Precio anterior</th>
                    <th class="text-end">Precio nuevo</th>
                    <th class="text-end">Variación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($historial as $cambio)
                    @php
                        $variacion = (float) $cambio->precionuevo - (float) $cambio->precioanterior;
                    @endphp
                    <tr>
                        <td class="small text-muted">{{ $cambio->fecha_registro?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">Bs. {{ number_format((float) $cambio->precioanterior, 2) }}</td>
                        <td class="text-end fw-semibold">Bs. {{ number_format((float) $cambio->precionuevo, 2) }}</td>
                        <td class="text-end">
                            @if ($variacion > 0)
                                <span class="badge text-bg-success">+ Bs. {{ number_format($variacion, 2) }}</span>
                            @elseif ($variacion < 0)
                                <span class="badge text-bg-danger">− Bs. {{ number_format(abs($variacion), 2) }}</span>
                            @else
                                <span class="badge text-bg-secondary">Sin cambio</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
