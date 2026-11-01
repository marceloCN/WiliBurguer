<div class="card tarjeta-producto">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold">📜 Historial de jornadas</span>
        <span class="badge text-bg-dark">{{ $historial->total() }}</span>
    </div>

    <div class="card-body">
        <form method="GET" action="{{ route('dashboard.caja.index') }}" class="row g-2 align-items-end mb-3">
            {{-- Conserva la sucursal actual: sin este campo el filtro volvería a
                 la primera sucursal permitida. --}}
            <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">

            <div class="col-6 col-md-3">
                <label for="idcaja" class="form-label small fw-bold mb-1">Caja</label>
                <select name="idcaja" id="idcaja" class="form-select form-select-sm">
                    <option value="">Todas las cajas</option>
                    @foreach ($cajasHistorial as $caja)
                        <option value="{{ $caja->id }}" @selected($idcaja == $caja->id)>{{ $caja->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="fecha_desde" class="form-label small fw-bold mb-1">Apertura desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
            </div>
            <div class="col-6 col-md-3">
                <label for="fecha_hasta" class="form-label small fw-bold mb-1">Apertura hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-brand">Filtrar</button>
                @if ($fechaDesde || $fechaHasta || $idcaja)
                    <a href="{{ route('dashboard.caja.index', ['idsucursal' => $sucursalActual->id]) }}"
                       class="btn btn-sm btn-outline-secondary">Limpiar</a>
                @endif
            </div>
        </form>

        @if ($historial->isEmpty())
            <div class="alert alert-light text-center mb-0">
                @if ($fechaDesde || $fechaHasta || $idcaja)
                    @php
                        $motivos = [];
                        if ($idcaja) {
                            $motivos[] = 'de la caja '.$cajasHistorial->firstWhere('id', $idcaja)?->nombre;
                        }
                        if ($fechaDesde || $fechaHasta) {
                            $motivos[] = 'en ese rango de fechas';
                        }
                    @endphp
                    No hay jornadas de caja en {{ $sucursalActual->nombre }} {{ implode(' ', $motivos) }}.
                @else
                    Aún no hay jornadas de caja en {{ $sucursalActual->nombre }}.
                @endif
            </div>
        @else
            <div class="accordion accordion-flush border rounded" id="historialCajaAccordion">
                @foreach ($historial as $jornada)
                    @php
                        $cierre = $jornada->cierreCaja;
                        $diferencia = $cierre?->diferencia;
                        $itemId = 'historial-item-'.$jornada->id;
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} py-2" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#{{ $itemId }}"
                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                <div class="d-flex flex-wrap align-items-center gap-2 w-100 pe-2">
                                    <span class="fw-bold">{{ $jornada->caja?->nombre }}</span>
                                    <span class="text-muted small">· {{ $jornada->empleado?->nombre }} {{ $jornada->empleado?->apellido }}</span>
                                    <span class="text-muted small ms-auto">
                                        {{ $jornada->fechaapertura->format('d/m/Y H:i') }}
                                        →
                                        {{ $cierre?->fechacierre?->format('d/m/Y H:i') ?? 'Abierta' }}
                                    </span>
                                    @if ($jornada->estado === 'ABIERTA')
                                        <span class="badge text-bg-success">Abierta</span>
                                    @elseif ($cierre)
                                        <span class="badge {{ $diferencia == 0 ? 'text-bg-primary' : 'text-bg-warning' }}">
                                            {{ $diferencia == 0 ? 'Cuadrada' : 'Con diferencia' }}
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">Cerrada</span>
                                    @endif
                                </div>
                            </button>
                        </h2>
                        <div id="{{ $itemId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                             data-bs-parent="#historialCajaAccordion">
                            <div class="accordion-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-6 col-md-3">
                                        <div class="small text-muted">Apertura</div>
                                        <div class="fw-semibold">{{ $jornada->fechaapertura->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="small text-muted">Cierre</div>
                                        <div class="fw-semibold">
                                            {{ $cierre?->fechacierre?->format('d/m/Y H:i') ?? '—' }}
                                            @if ($jornada->estado !== 'ABIERTA' && $cierre?->empleado)
                                                <div class="small text-muted">por {{ $cierre->empleado->nombre }} {{ $cierre->empleado->apellido }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="small text-muted">Efectivo esperado</div>
                                        <div class="fw-semibold">Bs. {{ number_format($cierre?->efectivoesperado ?? 0, 2) }}</div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="small text-muted">Efectivo contado</div>
                                        <div class="fw-semibold">Bs. {{ number_format($cierre?->efectivocontado ?? 0, 2) }}</div>
                                    </div>
                                </div>

                                @if ($diferencia !== null)
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="small text-muted">Diferencia:</span>
                                        <span class="fw-bold {{ $diferencia < 0 ? 'text-danger' : ($diferencia > 0 ? 'text-success' : 'text-primary') }}">
                                            {{ $diferencia < 0 ? '-' : ($diferencia > 0 ? '+' : '') }}Bs. {{ number_format(abs((float) $diferencia), 2) }}
                                        </span>
                                    </div>
                                @endif

                                @if ($cierre && $cierre->observacion)
                                    <div class="alert alert-light border small py-2 mb-3">
                                        📝 {{ $cierre->observacion }}
                                    </div>
                                @endif

                                <div class="d-flex flex-wrap gap-2">
                                    @if ($puedeReporteCierre && $jornada->estado !== 'ABIERTA' && $cierre)
                                        <a href="{{ route('dashboard.caja.reporte', $jornada) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            📄 Descargar reporte
                                        </a>
                                    @else
                                        <span class="text-muted small">
                                            {{ $jornada->estado === 'ABIERTA' ? 'La jornada sigue abierta.' : 'Reporte no disponible.' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $historial->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
