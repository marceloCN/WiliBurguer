<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="fw-bold">🔁 Movimientos de la jornada</span>
        <span class="badge text-bg-dark">{{ $movimientos->total() }}</span>
    </div>

    <div class="card-body">
        @if ($puedeMovimiento)
            <div class="card border mb-4">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-3">Registrar movimiento</h3>
                    <form method="POST" action="{{ route('dashboard.caja.movimiento.store') }}" class="row g-3">
                        @csrf
                        <input type="hidden" name="idaperturacaja" value="{{ $miTurno->id }}">

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold">Tipo <span class="text-danger">*</span></label>
                            <select name="tipomovimiento" class="form-select" required>
                                @foreach (App\Http\Controllers\CajaController::TIPOS_MOVIMIENTO as $tipo)
                                    <option value="{{ $tipo }}" @selected(old('tipomovimiento') === $tipo)>
                                        {{ $tipo === 'INGRESO' ? '➕ Ingreso' : '➖ Egreso' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-bold">Monto (Bs.) <span class="text-danger">*</span></label>
                            <input type="number" name="monto" class="form-control" required
                                   min="0.01" max="999999999.99" step="0.01" inputmode="decimal"
                                   value="{{ old('monto') }}">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-bold">Concepto <span class="text-danger">*</span></label>
                            <input type="text" name="concepto" class="form-control" required
                                   maxlength="255" value="{{ old('concepto') }}"
                                   placeholder="Ej. Proveedores, retiro de caja">
                        </div>

                        <div class="col-12 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-brand w-100">Registrar</button>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Observación</label>
                            <input type="text" name="observacion" class="form-control"
                                   maxlength="255" value="{{ old('observacion') }}">
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if ($movimientos->isEmpty())
            <div class="alert alert-light text-center mb-0">
                Sin movimientos registrados en esta jornada.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Empleado</th>
                            <th class="text-end">Monto</th>
                            @if ($puedeBajaMovimiento)
                                <th class="text-end">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movimientos as $movimiento)
                            @php
                                $esIngreso = $movimiento->tipomovimiento === 'INGRESO';
                            @endphp
                            <tr>
                                <td class="small text-nowrap">{{ $movimiento->fechamovimiento->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge {{ $esIngreso ? 'text-bg-success' : 'text-bg-danger' }}">
                                        {{ $movimiento->tipomovimiento }}
                                    </span>
                                </td>
                                <td>
                                    {{ $movimiento->concepto }}
                                    @if ($movimiento->observacion)
                                        <div class="small text-muted">{{ $movimiento->observacion }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $movimiento->empleado?->nombre }} {{ $movimiento->empleado?->apellido }}</td>
                                <td class="text-end fw-semibold {{ $esIngreso ? 'text-success' : 'text-danger' }}">
                                    {{ $esIngreso ? '+' : '-' }} Bs. {{ number_format($movimiento->monto, 2) }}
                                </td>
                                @if ($puedeBajaMovimiento)
                                    <td class="text-end">
                                        <form method="POST"
                                              action="{{ route('dashboard.caja.movimiento.baja', $movimiento) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('¿Dar de baja este movimiento?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ Dar de baja</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $movimientos->onEachSide(1)->links() }}
        @endif
    </div>
</div>
