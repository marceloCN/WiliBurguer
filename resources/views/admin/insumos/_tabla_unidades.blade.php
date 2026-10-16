<div class="card tarjeta-producto h-100">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold">Unidades de medida</span>
        <span class="badge text-bg-dark">{{ $unidades->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Abrev.</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($unidades as $unidad)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $unidad->nombre }}</div>
                                <div class="small text-muted">{{ $tiposUnidad[$unidad->tipo] ?? $unidad->tipo }}</div>
                            </td>
                            <td>
                                <span class="badge text-bg-light border">{{ $unidad->abreviatura }}</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-brand"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editarUnidadModal"
                                        data-id="{{ $unidad->id }}"
                                        data-nombre="{{ $unidad->nombre }}"
                                        data-abreviatura="{{ $unidad->abreviatura }}"
                                        data-tipo="{{ $unidad->tipo }}">
                                    Editar
                                </button>
                                @if ($puedeDesactivarUnidad && ! ($unidad->estado && in_array($unidad->id, $unidadesEnUso)))
                                    <form method="POST" action="{{ route('dashboard.insumos.unidad.estado', $unidad) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            {{ $unidad->estado ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                            data-mensaje="{{ $puedeDesactivarUnidad ? 'No puedes desactivar esta unidad porque hay insumos activos que la usan.' : 'No tienes permiso para desactivar unidades de medida. Solicita el permiso <strong>335</strong>.' }}">
                                        {{ $unidad->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                No hay unidades creadas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
