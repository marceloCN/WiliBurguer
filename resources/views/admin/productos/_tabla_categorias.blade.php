<div class="card tarjeta-producto h-100">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold">Categorías</span>
        <span class="badge text-bg-dark">{{ $categorias->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categorias as $categoria)
                        <tr>
                            <td class="fw-semibold">{{ $categoria->nombre }}</td>
                            <td>
                                @if ($categoria->estado)
                                    <span class="badge text-bg-success">Activa</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($puedeEditarCategoria)
                                    <button type="button" class="btn btn-sm btn-outline-brand"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editarCategoriaModal"
                                            data-id="{{ $categoria->id }}"
                                            data-nombre="{{ $categoria->nombre }}">
                                        Editar
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-brand"
                                            data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                            data-mensaje="No tienes permiso para editar categorías. Solicita el permiso <strong>301</strong>.">
                                        Editar
                                    </button>
                                @endif

                                @if ($puedeDesactivarCategoria && ! ($categoria->estado && in_array($categoria->id, $categoriasEnUso)))
                                    <form method="POST"
                                          action="{{ route('dashboard.productos.categoria.estado', $categoria) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            {{ $categoria->estado ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                            data-mensaje="{{ $puedeDesactivarCategoria ? 'No puedes desactivar esta categoría porque hay productos activos que la usan.' : 'No tienes permiso para dar de baja categorías. Solicita el permiso <strong>305</strong>.' }}">
                                        {{ $categoria->estado ? 'Desactivar' : 'Activar' }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                No hay categorías creadas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
