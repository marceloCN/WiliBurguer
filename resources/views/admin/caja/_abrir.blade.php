<div class="card tarjeta-producto mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span class="fw-bold">🔓 Abrir caja</span>
        <span class="badge text-bg-dark">Permiso 410</span>
    </div>

    <div class="card-body">
        <p class="small text-muted mb-3">
            Registra el <strong>fondo inicial</strong> en efectivo con el que empiezas la jornada.
            No puedes abrir dos veces la misma caja sin cerrarla antes.
        </p>

        <form method="POST" action="{{ route('dashboard.caja.abrir') }}">
            @csrf
            <input type="hidden" name="idsucursal" value="{{ $sucursalActual->id }}">

            <div class="mb-3">
                <label class="form-label small fw-bold">Caja <span class="text-danger">*</span></label>
                <select name="idcaja" class="form-select" required>
                    <option value="" disabled selected>Selecciona una caja...</option>
                    @foreach ($cajasLibres as $caja)
                        <option value="{{ $caja->id }}" @selected(old('idcaja') == $caja->id)>
                            {{ $caja->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Fondo inicial (Bs.) <span class="text-danger">*</span></label>
                <input type="number" name="montoinicial" class="form-control" required
                       min="0" max="999999999.99" step="0.01" inputmode="decimal"
                       value="{{ old('montoinicial', '0.00') }}">
            </div>

            <button type="submit" class="btn btn-brand w-100">🔓 Abrir caja</button>
        </form>
    </div>
</div>
