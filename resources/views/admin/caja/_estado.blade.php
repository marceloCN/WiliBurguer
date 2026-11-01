<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold">🏪 Cajas de {{ $sucursalActual->nombre }}</span>
        <span class="badge text-bg-dark">{{ $cajas->count() }}</span>
    </div>

    <div class="list-group list-group-flush">
        @forelse ($cajas as $caja)
            @php
                $apertura = $caja->aperturasCaja->first();
            @endphp
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1">
                        <div class="fw-bold">{{ $caja->nombre }}</div>
                        @if ($apertura)
                            <div class="small text-muted">
                                Abierta por {{ $apertura->empleado?->nombre }} {{ $apertura->empleado?->apellido }}
                                · {{ $apertura->fechaapertura->format('d/m/Y H:i') }}
                            </div>
                            <div class="small text-muted">
                                Fondo inicial: Bs. {{ number_format($apertura->montoinicial, 2) }}
                            </div>
                            @if ($puedeCerrar && (int) $apertura->idempleado !== (int) $empleado->id)
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                        data-bs-toggle="modal" data-bs-target="#cerrarCajaModal"
                                        data-accion="{{ route('dashboard.caja.cerrar', $apertura) }}"
                                        data-caja="{{ $caja->nombre }}"
                                        data-empleado="{{ $apertura->empleado?->nombre }} {{ $apertura->empleado?->apellido }}"
                                        data-esperado="{{ number_format($apertura->cierreCaja?->efectivoesperado ?? 0, 2) }}">
                                    🔒 Cerrar jornada
                                </button>
                            @endif
                        @else
                            <div class="small text-muted">Sin jornada abierta</div>
                        @endif
                    </div>

                    <span class="badge rounded-pill {{ $apertura ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ $apertura ? 'Abierta' : 'Cerrada' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="list-group-item text-muted small">
                Esta sucursal no tiene cajas registradas.
            </div>
        @endforelse
    </div>
</div>
