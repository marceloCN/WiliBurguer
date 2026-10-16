@if ($puedeGestionarCajas)
    <div class="tab-pane fade" id="tab-cajas" role="tabpanel" aria-labelledby="tab-cajas-tab">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card tarjeta-producto">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-1">Registrar caja</h2>
                        <p class="small text-muted mb-3">
                            Cada caja recibe un <strong>nombre</strong> que no puede repetirse dentro de esta
                            sucursal, y solo puede haber <strong>dos cajas activas</strong> a la vez.
                            La caja no se asocia a un cajero: sirve para identificar la apertura y cualquier
                            cajero puede abrirla con solo conocer su nombre.
                        </p>
                        <form method="POST" action="{{ route('dashboard.sucursales.caja.store', $sucursal) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" required
                                        maxlength="100" value="{{ old('nombre') }}"
                                        placeholder="Ej. Caja 1">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Estado inicial</label>
                                    <select name="activo" class="form-select">
                                        <option value="1" @selected(old('activo', 1))>Activa</option>
                                        <option value="0" @selected(old('activo') === '0')>Inactiva</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-brand w-100">Registrar caja</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card tarjeta-producto">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Cajas de esta sucursal</h2>
                        @if ($sucursal->cajas->isEmpty())
                            <div class="alert alert-light text-center mb-0">
                                Esta sucursal aún no tiene cajas registradas.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Caja</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sucursal->cajas as $caja)
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold">{{ $caja->nombre }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill {{ $caja->activo ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                        {{ $caja->activo ? 'Activa' : 'Inactiva' }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-brand"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#editar-caja-{{ $caja->id }}"
                                                        aria-expanded="false"
                                                        aria-controls="editar-caja-{{ $caja->id }}">
                                                        ✏️ Editar
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr class="table-light">
                                                <td colspan="3" class="p-0 border-0">
                                                    <div class="collapse" id="editar-caja-{{ $caja->id }}">
                                                        <div class="p-3">
                                                            <form method="POST"
                                                                action="{{ route('dashboard.sucursales.caja.update', [$sucursal, $caja]) }}"
                                                                class="row g-2 align-items-end">
                                                                @csrf
                                                                <div class="col-6 col-md-4">
                                                                    <label class="form-label small fw-bold d-block">Nombre</label>
                                                                    <input type="text" name="nombre"
                                                                        class="form-control form-control-sm" maxlength="100"
                                                                        value="{{ $caja->nombre }}" required>
                                                                </div>
                                                                <div class="col-6 col-md-3">
                                                                    <label class="form-label small fw-bold d-block">Estado</label>
                                                                    <select name="activo" class="form-select form-select-sm">
                                                                        <option value="1" @selected($caja->activo)>Activa</option>
                                                                        <option value="0" @selected(!$caja->activo)>Inactiva</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-12 col-md-5 gap-1 d-flex">
                                                                    <button type="submit" class="btn btn-sm btn-brand flex-fill">✓ Guardar</button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                        data-bs-toggle="collapse"
                                                                        data-bs-target="#editar-caja-{{ $caja->id }}">
                                                                        Cancelar
                                                                    </button>
                                                                </div>
                                                            </form>
                                                            @if ($caja->activo)
                                                                <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#modal-confirmar-baja"
                                                                    data-accion="{{ route('dashboard.sucursales.caja.baja', [$sucursal, $caja]) }}"
                                                                    data-mensaje="¿Dar de baja la caja {{ $caja->nombre }}?">
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
