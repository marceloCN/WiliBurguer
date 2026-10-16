@if ($puedeGestionarMesas)
    <div class="tab-pane fade" id="tab-mesas" role="tabpanel" aria-labelledby="tab-mesas-tab">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card tarjeta-producto">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-1">Registrar mesa</h2>
                        <p class="small text-muted mb-3">
                            Asigna a cada mesa un <strong>número</strong> y cuántas <strong>personas</strong>
                            puede sentar. El número de mesa <strong>no puede repetirse</strong> dentro de
                            esta sucursal.
                        </p>
                        <form method="POST" action="{{ route('dashboard.sucursales.mesa.store', $sucursal) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Número <span class="text-danger">*</span></label>
                                    <input type="number" name="numeromesa" class="form-control" min="1"
                                        required value="{{ old('numeromesa') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-bold">Capacidad <span class="text-danger">*</span></label>
                                    <input type="number" name="capacidad" class="form-control" min="1"
                                        required value="{{ old('capacidad', 4) }}">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-brand w-100">+ Registrar mesa</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card tarjeta-producto">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Mesas de esta sucursal</h2>
                        @if ($sucursal->mesas->isEmpty())
                            <div class="alert alert-light text-center mb-0">
                                Esta sucursal aún no tiene mesas registradas.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mesa</th>
                                            <th>Capacidad</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sucursal->mesas->sortBy('numeromesa') as $mesa)
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold">Mesa {{ $mesa->numeromesa }}</span>
                                                </td>
                                                <td>
                                                    {{ $mesa->capacidad }}
                                                    <span class="text-muted small">personas</span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill {{ $mesa->estado ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                        {{ $mesa->estado ? 'Activa' : 'Inactiva' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-brand"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#editar-mesa-{{ $mesa->id }}"
                                                        aria-expanded="false"
                                                        aria-controls="editar-mesa-{{ $mesa->id }}">
                                                        ✏️ Editar
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="table-light">
                                                <td colspan="4" class="p-0 border-0">
                                                    <div class="collapse" id="editar-mesa-{{ $mesa->id }}">
                                                        <div class="p-3">
                                                            <form method="POST"
                                                                action="{{ route('dashboard.sucursales.mesa.update', [$sucursal, $mesa]) }}"
                                                                class="row g-2 align-items-end">
                                                                @csrf
                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label small fw-bold d-block">Número</label>
                                                                    <input type="number" name="numeromesa"
                                                                        class="form-control form-control-sm" min="1"
                                                                        value="{{ $mesa->numeromesa }}" required>
                                                                </div>
                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label small fw-bold d-block">Capacidad</label>
                                                                    <input type="number" name="capacidad"
                                                                        class="form-control form-control-sm" min="1"
                                                                        value="{{ $mesa->capacidad }}" required>
                                                                </div>
                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label small fw-bold d-block">Estado</label>
                                                                    <select name="estado" class="form-select form-select-sm">
                                                                        <option value="1" @selected($mesa->estado)>Activa</option>
                                                                        <option value="0" @selected(!$mesa->estado)>Inactiva</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-6 col-md-3 gap-1 d-flex">
                                                                    <button type="submit" class="btn btn-sm btn-brand flex-fill">✓ Guardar</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#editar-mesa-{{ $mesa->id }}">
                                                                        Cancelar
                                                                    </button>
                                                                </div>
                                                            </form>
                                                            @if ($mesa->estado)
                                                                <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#modal-confirmar-baja"
                                                                    data-accion="{{ route('dashboard.sucursales.mesa.baja', [$sucursal, $mesa]) }}"
                                                                    data-mensaje="¿Dar de baja la Mesa {{ $mesa->numeromesa }}?">
                                                                    🗑️ Dar de baja
                                                                </button>
                                                            @endif
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
@endif
