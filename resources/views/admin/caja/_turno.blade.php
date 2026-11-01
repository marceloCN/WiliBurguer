@php
    $cierre = $miTurno->cierreCaja;
@endphp

<div class="accordion accordion-flush mb-4" id="turnoCajaAccordion">
    <div class="accordion-item border rounded shadow-sm">
        <h2 class="accordion-header">
            <button class="accordion-button py-3" type="button" data-bs-toggle="collapse" data-bs-target="#turnoCajaCollapse" aria-expanded="true">
                <div class="d-flex align-items-center gap-2 w-100 pe-2">
                    <span class="fw-bold">💰 Jornada en curso · {{ $miTurno->caja?->nombre }}</span>
                    <span class="badge text-bg-success ms-auto">Abierta</span>
                </div>
            </button>
        </h2>
        <div id="turnoCajaCollapse" class="accordion-collapse collapse show" data-bs-parent="#turnoCajaAccordion">
            <div class="accordion-body">
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">Abierta por</div>
                            <div class="fw-bold">{{ $empleado->nombre }} {{ $empleado->apellido }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">Desde</div>
                            <div class="fw-bold">{{ $miTurno->fechaapertura->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">Fondo inicial</div>
                            <div class="fw-bold precio">Bs. {{ number_format($miTurno->montoinicial, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100 border-success">
                            <div class="small text-muted">Efectivo esperado</div>
                            <div class="fw-bold precio">Bs. {{ number_format($cierre?->efectivoesperado ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                <h3 class="h6 fw-bold text-uppercase text-muted mb-3">Ventas del turno por medio de pago</h3>
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">💵 Efectivo</div>
                            <div class="fw-bold">Bs. {{ number_format($cierre?->totalventasefectivo ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">📱 QR / transferencia</div>
                            <div class="fw-bold">Bs. {{ number_format($cierre?->totalventasqr ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">💳 Tarjeta</div>
                            <div class="fw-bold">Bs. {{ number_format($cierre?->totalventastarjeta ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100 border-brand">
                            <div class="small text-muted">Total ventas</div>
                            <div class="fw-bold precio">Bs. {{ number_format($cierre?->totalventas ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">➕ Ingresos</div>
                            <div class="fw-bold text-success">+ Bs. {{ number_format($cierre?->totalingresos ?? 0, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="small text-muted">➖ Egresos</div>
                            <div class="fw-bold text-danger">- Bs. {{ number_format($cierre?->totalegresos ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if ($puedeCerrar)
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cerrarCajaModal"
                                data-accion="{{ route('dashboard.caja.cerrar', $miTurno) }}"
                                data-caja="{{ $miTurno->caja?->nombre }}"
                                data-empleado="{{ $empleado->nombre }} {{ $empleado->apellido }}"
                                data-esperado="{{ number_format($cierre?->efectivoesperado ?? 0, 2) }}">
                            🔒 Cerrar caja
                        </button>
                    @endif

                    @if ($puedeAbrir && $cajasLibres->isNotEmpty())
                        <span class="badge text-bg-light border align-self-center">
                            Puedes abrir otra caja en el panel lateral.
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
