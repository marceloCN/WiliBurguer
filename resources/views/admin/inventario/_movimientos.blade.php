<div class="card tarjeta-producto">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Movimientos recientes</span>
        <span class="badge text-bg-dark">{{ $movimientos->total() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Insumo</th>
                        <th>Cantidad</th>
                        <th class="text-center">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movimientos as $movimiento)
                        <tr>
                            <td class="small text-nowrap">{{ $movimiento->fechamovimiento->format('d/m/Y H:i') }}</td>
                            <td>
                                @php
                                    $tipoClase = match ($movimiento->tipomovimiento) {
                                        'ENTRADA' => 'text-bg-success',
                                        'SALIDA' => 'text-bg-danger',
                                        default => 'text-bg-warning',
                                    };
                                @endphp
                                <span class="badge {{ $tipoClase }}">{{ $movimiento->tipomovimiento }}</span>
                            </td>
                            <td class="fw-semibold">{{ $movimiento->inventarioSucursal?->insumo?->nombre }}</td>
                            <td class="fw-semibold">{{ rtrim(rtrim(number_format($movimiento->cantidad, 3, '.', ''), '0'), '.') }}</td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#detalleMovimientoModal"
                                        data-fecha="{{ $movimiento->fechamovimiento->format('d/m/Y H:i') }}"
                                        data-tipo="{{ $movimiento->tipomovimiento }}"
                                        data-insumo="{{ $movimiento->inventarioSucursal?->insumo?->nombre ?? '—' }}"
                                        data-unidad="{{ $movimiento->inventarioSucursal?->insumo?->unidadMedida?->abreviatura ?? '—' }}"
                                        data-cantidad="{{ rtrim(rtrim(number_format($movimiento->cantidad, 3, '.', ''), '0'), '.') }}"
                                        data-anterior="{{ rtrim(rtrim(number_format($movimiento->cantidadanterior, 3, '.', ''), '0'), '.') }}"
                                        data-nuevo="{{ rtrim(rtrim(number_format($movimiento->cantidadnueva, 3, '.', ''), '0'), '.') }}"
                                        data-empleado="{{ $movimiento->empleado ? $movimiento->empleado->nombre.' '.$movimiento->empleado->apellido : '—' }}"
                                        data-observacion="{{ $movimiento->observacion ?? '—' }}">
                                    Ver detalle
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Aún no hay movimientos registrados en esta sucursal.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($movimientos->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $movimientos->firstItem() }}–{{ $movimientos->lastItem() }}
                    de {{ $movimientos->total() }} movimientos
                </span>
                <nav aria-label="Paginación de movimientos">
                    {{ $movimientos->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
