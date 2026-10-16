<div class="card tarjeta-producto">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Insumos</span>
        <span class="badge text-bg-brand">{{ $insumos->total() }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.insumos.index') }}" class="mb-3">
            <div class="input-group">
                <input type="text" name="busqueda" id="busqueda-insumos" class="form-control" placeholder="Buscar insumo por nombre…"
                       value="{{ $busqueda }}">
                <button type="submit" class="btn btn-outline-brand">Buscar</button>
                @if ($busqueda)
                    <a href="{{ route('dashboard.insumos.index') }}" class="btn btn-outline-secondary">Limpiar</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tabla-insumos">
                <thead class="bg-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Unidad de medida</th>
                        <th>Descripción</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($insumos as $insumo)
                        <tr>
                            <td class="fw-semibold">{{ $insumo->nombre }}</td>
                            <td>
                                <span class="badge text-bg-light border">
                                    {{ $insumo->unidadMedida?->nombre ?? '—' }}
                                    ({{ $insumo->unidadMedida?->abreviatura ?? '—' }})
                                </span>
                            </td>
                            <td class="small text-muted">{{ $insumo->descripcion ?? '—' }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-brand"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editarInsumoModal"
                                        data-id="{{ $insumo->id }}"
                                        data-nombre="{{ $insumo->nombre }}"
                                        data-descripcion="{{ $insumo->descripcion }}"
                                        data-unidad="{{ $insumo->idunidadmedida }}">
                                    Editar
                                </button>
                                @if ($puedeDesactivarInsumo)
                                    <form method="POST" action="{{ route('dashboard.insumos.estado', $insumo) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            {{ $insumo->estado ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                            data-mensaje="No tienes permiso para desactivar insumos. Solicita el permiso <strong>338</strong>.">
                                        {{ $insumo->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                No se encontraron insumos. Pulsa "+ Insumo" para crear el primero.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($insumos->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $insumos->firstItem() }}–{{ $insumos->lastItem() }}
                    de {{ $insumos->total() }} insumos
                </span>
                <nav aria-label="Paginación de insumos">
                    {{ $insumos->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
