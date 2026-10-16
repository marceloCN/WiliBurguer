<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Stock · {{ $sucursalActual->nombre }}</span>
        <span class="badge text-bg-brand">{{ $inventario->total() }} insumo(s)</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Insumo</th>
                        <th>Unidad</th>
                        <th>Cantidad</th>
                        <th>Mínimo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventario as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item->insumo?->nombre }}</td>
                            <td>
                                <span class="badge text-bg-light border">{{ $item->insumo?->unidadMedida?->abreviatura ?? '—' }}</span>
                            </td>
                            <td class="fw-semibold">{{ rtrim(rtrim(number_format($item->cantidad, 3, '.', ''), '0'), '.') }}</td>
                            <td>{{ $item->cantidadminima !== null ? rtrim(rtrim(number_format($item->cantidadminima, 3, '.', ''), '0'), '.') : '—' }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-brand"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editarMinimoModal"
                                        data-id="{{ $item->id }}"
                                        data-insumo="{{ $item->insumo?->nombre }}"
                                        data-minimo="{{ $item->cantidadminima }}">
                                    Editar mín.
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No hay insumos cargados en esta sucursal. Pulsa "+ Registrar movimiento" para comenzar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($inventario->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $inventario->firstItem() }}–{{ $inventario->lastItem() }}
                    de {{ $inventario->total() }} insumos
                </span>
                <nav aria-label="Paginación de stock">
                    {{ $inventario->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
