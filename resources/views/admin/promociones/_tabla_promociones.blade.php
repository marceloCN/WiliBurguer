<div class="card tarjeta-producto">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">Promociones de {{ $sucursalActual->nombre }}</span>
        <span class="badge text-bg-brand">{{ $promociones->total() }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.promociones.index') }}" class="mb-3">
            <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">
            <div class="row g-2">
                <div class="col-12 col-md-8">
                    <input type="text" name="busqueda" class="form-control"
                           placeholder="Buscar promoción por nombre…" value="{{ $busqueda }}">
                </div>
                <div class="col-12 col-md-4 d-grid">
                    <button type="submit" class="btn btn-outline-brand">Buscar</button>
                </div>
            </div>
            @if ($busqueda !== '')
                <a href="{{ route('dashboard.promociones.index', ['idsucursal' => $sucursalActual->id]) }}"
                   class="small d-inline-block mt-2">Limpiar filtros</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Promoción</th>
                        <th>Descuento</th>
                        <th class="d-none d-md-table-cell">Vigencia</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promociones as $promocion)
                        @php
                            $hoy = now()->dayOfWeekIso;
                            $dias = $promocion->dias->sortBy('diasemana')->values();
                            $productos = $promocion->productosSucursal;
                            $aplicaHoy = $promocion->activo
                                && $promocion->dias->contains('diasemana', $hoy)
                                && now()->between($promocion->fechainicio, $promocion->fechafin);
                            $nombresDias = $dias->map(fn ($dia) => \App\Models\HorarioSucursal::nombreDia($dia->diasemana))->implode(', ');
                            $nombresProductos = $productos->map(fn ($p) => $p->producto?->nombre)->filter()->implode(', ');
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($promocion->promocion?->photo)
                                        <img src="{{ asset('storage/'.$promocion->promocion->photo) }}"
                                             alt="{{ $promocion->promocion?->nombre }}"
                                             class="rounded border object-fit-cover flex-shrink-0" width="48" height="48">
                                    @else
                                        <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted flex-shrink-0"
                                              style="width: 48px; height: 48px;">🎉</span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate" style="max-width: 160px;"
                                             title="{{ $promocion->promocion?->nombre ?? '—' }}">
                                            {{ $promocion->promocion?->nombre ?? '—' }}
                                        </div>
                                        <div class="small text-muted d-md-none">
                                            {{ $promocion->fechainicio->format('d/m/Y') }} → {{ $promocion->fechafin->format('d/m/Y') }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge text-bg-danger">{{ $promocion->etiquetaDescuento() }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div class="small text-muted">
                                    {{ $promocion->fechainicio->format('d/m/Y') }} → {{ $promocion->fechafin->format('d/m/Y') }}
                                </div>
                            </td>
                            <td>
                                @if ($promocion->activo)
                                    <span class="badge text-bg-success">Activa</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactiva</span>
                                @endif
                                @if ($aplicaHoy)
                                    <div class="small text-success mt-1">Aplica hoy</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detallePromocionModal"
                                            data-nombre="{{ $promocion->promocion?->nombre }}"
                                            data-photo="{{ $promocion->promocion?->photo ? asset('storage/'.$promocion->promocion->photo) : '' }}"
                                            data-descuento="{{ $promocion->etiquetaDescuento() }}"
                                            data-estado="{{ $promocion->activo ? 'Activa' : 'Inactiva' }}"
                                            data-vigencia="{{ $promocion->fechainicio->format('d/m/Y') }} → {{ $promocion->fechafin->format('d/m/Y') }}"
                                            data-dias="{{ $nombresDias }}"
                                            data-productos="{{ $nombresProductos }}"
                                            data-aplica-hoy="{{ $aplicaHoy ? '1' : '0' }}">
                                        Ver
                                    </button>

                                    @if ($puedeEditar)
                                        <button type="button" class="btn btn-sm btn-outline-brand"
                                                data-bs-toggle="modal"
                                                data-bs-target="#promocionModal"
                                                data-modo="editar"
                                                data-id="{{ $promocion->id }}"
                                                data-nombre="{{ $promocion->promocion?->nombre }}"
                                                data-tipo="{{ $promocion->tipodescuento }}"
                                                data-valor="{{ $promocion->valordescuento }}"
                                                data-inicio="{{ $promocion->fechainicio->format('Y-m-d') }}"
                                                data-fin="{{ $promocion->fechafin->format('Y-m-d') }}"
                                                data-photo="{{ $promocion->promocion?->photo }}"
                                                data-dias="{{ json_encode($dias->pluck('diasemana')->values()->all()) }}"
                                                data-productos="{{ json_encode($productos->mapWithKeys(fn ($p) => [$p->id => (int) $p->pivot->cantidad_producto])->all()) }}">
                                            Editar
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-brand"
                                                data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                                data-mensaje="No tienes permiso para editar promociones. Solicita el permiso <strong>351</strong>.">
                                            Editar
                                        </button>
                                    @endif

                                    @if ($puedeDarBaja)
                                        <form method="POST" action="{{ route('dashboard.promociones.estado', $promocion) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                {{ $promocion->activo ? 'Dar de baja' : 'Reactivar' }}
                                            </button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                data-bs-toggle="modal" data-bs-target="#sinPermisoModal"
                                                data-mensaje="No tienes permiso para dar de baja promociones. Solicita el permiso <strong>355</strong>.">
                                            {{ $promocion->activo ? 'Dar de baja' : 'Reactivar' }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                @if ($busqueda !== '')
                                    Ninguna promoción coincide con la búsqueda.
                                @else
                                    No hay promociones en {{ $sucursalActual->nombre }}. Pulsa "+ Promoción" para crear la primera.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($promociones->hasPages())
        <div class="card-footer bg-light border-top py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-muted">
                <span>
                    Mostrando {{ $promociones->firstItem() }}–{{ $promociones->lastItem() }}
                    de {{ $promociones->total() }} promoción(es)
                </span>
                <nav aria-label="Paginación de promociones">
                    {{ $promociones->onEachSide(1)->links() }}
                </nav>
            </div>
        </div>
    @endif
</div>
