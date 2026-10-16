<div class="tab-pane fade" id="tab-horarios" role="tabpanel" aria-labelledby="tab-horarios-tab">
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card tarjeta-producto">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-1">Agregar horario</h2>
                    <p class="small text-muted mb-3">
                        Define el horario de atención de cada día: la <strong>hora de cierre debe ser
                        posterior a la apertura</strong> y solo puede existir <strong>un horario por día</strong>.
                        Si un día no atienden, déjalo sin registrar.
                    </p>
                    <form method="POST" action="{{ route('dashboard.sucursales.horario.store', $sucursal) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Día <span class="text-danger">*</span></label>
                                <select name="diasemana" class="form-select" required>
                                    @foreach (range(1, 7) as $dia)
                                        <option value="{{ $dia }}" @selected(old('diasemana') == $dia)>
                                            {{ App\Models\HorarioSucursal::nombreDia($dia) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Apertura <span class="text-danger">*</span></label>
                                <input type="time" name="horaapertura" class="form-control" required
                                    value="{{ old('horaapertura') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Cierre <span class="text-danger">*</span></label>
                                <input type="time" name="horacierre" class="form-control" required
                                    value="{{ old('horacierre') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Estado inicial</label>
                                <select name="activo" class="form-select">
                                    <option value="1" @selected(old('activo', 1))>Activo</option>
                                    <option value="0" @selected(old('activo') === '0')>Inactivo</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-brand w-100">+ Agregar horario</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card tarjeta-producto">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-3">Horarios registrados</h2>
                    @if ($sucursal->horarios->isEmpty())
                        <div class="alert alert-light text-center mb-0">
                            Esta sucursal aún no tiene horarios registrados.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Día</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sucursal->horarios->sortBy('diasemana') as $horario)
                                        <tr>
                                            <td>
                                                <span class="fw-semibold">
                                                    {{ App\Models\HorarioSucursal::nombreDia($horario->diasemana) }}
                                                </span>
                                            </td>
                                            <td class="font-monospace">
                                                {{ substr($horario->horaapertura, 0, 5) }} –
                                                {{ substr($horario->horacierre, 0, 5) }}
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill {{ $horario->activo ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                    {{ $horario->activo ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-brand"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#editar-horario-{{ $horario->idhorariosucursal }}"
                                                    aria-expanded="false"
                                                    aria-controls="editar-horario-{{ $horario->idhorariosucursal }}">
                                                    ✏️ Editar
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="table-light">
                                            <td colspan="4" class="p-0 border-0">
                                                <div class="collapse" id="editar-horario-{{ $horario->idhorariosucursal }}">
                                                    <div class="p-3">
                                                        <form method="POST"
                                                            action="{{ route('dashboard.sucursales.horario.update', [$sucursal, $horario]) }}"
                                                            class="row g-2 align-items-end">
                                                            @csrf
                                                            <div class="col-6 col-md-3">
                                                                <label class="form-label small fw-bold d-block">Día</label>
                                                                <select name="diasemana" class="form-select form-select-sm">
                                                                    @foreach (range(1, 7) as $dia)
                                                                        <option value="{{ $dia }}"
                                                                            @selected($horario->diasemana === $dia)>
                                                                            {{ App\Models\HorarioSucursal::nombreDia($dia) }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-5 col-md-3">
                                                                <label class="form-label small fw-bold d-block">Apertura</label>
                                                                <input type="time" name="horaapertura"
                                                                    value="{{ substr($horario->horaapertura, 0, 5) }}"
                                                                    class="form-control form-control-sm" required>
                                                            </div>
                                                            <div class="col-2 col-md-1 text-center text-muted pb-1">–</div>
                                                            <div class="col-5 col-md-3">
                                                                <label class="form-label small fw-bold d-block">Cierre</label>
                                                                <input type="time" name="horacierre"
                                                                    value="{{ substr($horario->horacierre, 0, 5) }}"
                                                                    class="form-control form-control-sm" required>
                                                            </div>
                                                            <div class="col-6 col-md-2">
                                                                <label class="form-label small fw-bold d-block">Estado</label>
                                                                <select name="activo" class="form-select form-select-sm">
                                                                    <option value="1" @selected($horario->activo)>Activo</option>
                                                                    <option value="0" @selected(!$horario->activo)>Inactivo</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-6 col-md-3 d-flex gap-1">
                                                                <button type="submit" class="btn btn-sm btn-brand flex-fill">✓ Guardar</button>
                                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                    data-bs-toggle="collapse"
                                                                    data-bs-target="#editar-horario-{{ $horario->idhorariosucursal }}">
                                                                    Cancelar
                                                                </button>
                                                            </div>
                                                        </form>
                                                        <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#modal-confirmar-baja"
                                                            data-accion="{{ route('dashboard.sucursales.horario.baja', [$sucursal, $horario]) }}"
                                                            data-mensaje="¿Eliminar el horario del {{ App\Models\HorarioSucursal::nombreDia($horario->diasemana) }}?">
                                                            🗑️ Eliminar
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
