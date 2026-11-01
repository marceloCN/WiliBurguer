{{-- Modal de cierre de jornada: lo dispara el botón propio (panel de turno) o
     cualquier botón "Cerrar jornada" de otra caja; cada botón aporta la ruta,
     la caja, quién la abrió y el efectivo esperado (ver admin.caja.index). --}}
<div class="modal fade" id="cerrarCajaModal" tabindex="-1" aria-hidden="true" aria-labelledby="cerrarCajaModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="form-cerrar-jornada" action="{{ $miTurno ? route('dashboard.caja.cerrar', $miTurno) : '#' }}">
                @csrf

                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="cerrarCajaModalLabel">🔒 Cerrar caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <p class="small text-muted">
                        Cuenta el efectivo de la caja e ingresa el monto. El sistema calcula la
                        <strong>diferencia</strong> contra el efectivo esperado.
                    </p>

                    <div class="alert alert-light border small">
                        <div class="d-flex justify-content-between">
                            <span>Jornada</span>
                            <strong id="cerrar-jornada-caja">{{ $miTurno?->caja?->nombre ?? '—' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Abierta por</span>
                            <strong id="cerrar-jornada-empleado">
                                {{ $miTurno ? $empleado->nombre.' '.$empleado->apellido : '—' }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Efectivo esperado</span>
                            <strong id="cerrar-jornada-esperado">
                                Bs. {{ number_format($miTurno?->cierreCaja?->efectivoesperado ?? 0, 2) }}
                            </strong>
                        </div>
                    </div>

                    <p class="small text-muted mb-0">
                        La jornada quedará cerrada <strong>a tu nombre</strong>
                        ({{ $empleado->nombre }} {{ $empleado->apellido }}), aunque la haya abierto otra persona.
                    </p>

                    <div class="mb-3 mt-3">
                        <label class="form-label small fw-bold">
                            Efectivo contado (Bs.) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="efectivocontado" class="form-control" required
                               min="0" max="999999999.99" step="0.01" inputmode="decimal"
                               value="{{ old('efectivocontado') }}">
                    </div>

                    <div class="mb-0">
                        <label class="form-label small fw-bold">Observación</label>
                        <textarea name="observacion" class="form-control" rows="2"
                                  maxlength="255" placeholder="Ej. Faltante por cambio mal entregado.">{{ old('observacion') }}</textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Cerrar caja</button>
                </div>
            </form>
        </div>
    </div>
</div>
