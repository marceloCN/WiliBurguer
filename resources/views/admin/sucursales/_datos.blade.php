<div class="tab-pane fade show active" id="tab-datos" role="tabpanel" aria-labelledby="tab-datos-tab">
    <div class="row g-4">
        @if ($esAdmin)
            <div class="col-12">
                <div class="card tarjeta-producto mb-4 border-warning-subtle">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge text-bg-brand px-3 py-2">👤 Gerente asignado</span>
                        </div>
                        <form method="POST" action="{{ route('dashboard.sucursales.gerente', $sucursal) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Gerente del sistema *</label>
                                    <div class="row g-2">
                                        <div class="col-md-9">
                                            <select name="idempleadogerente" class="form-select" required>
                                                @if ($sucursal->gerente)
                                                    <option value="{{ $sucursal->gerente->id }}" @selected(true)>
                                                        {{ $sucursal->gerente->nombre }}
                                                        {{ $sucursal->gerente->apellido }} (actual)
                                                    </option>
                                                @endif
                                                <option value="">— Sin gerente —</option>
                                                @foreach ($gerentesDisponibles as $gerente)
                                                    <option value="{{ $gerente->id }}"
                                                        @selected(old('idempleadogerente') == $gerente->id)>
                                                        {{ $gerente->nombre }} {{ $gerente->apellido }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="submit" class="btn btn-brand">
                                                <i class="bi bi-person-check"></i> Asignar gerente
                                            </button>
                                        </div>
                                    </div>
                                    <p class="small text-muted mt-2 mb-0">
                                        Solo se muestran gerentes del sistema que no están asignados a otra
                                        sucursal.
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="{{ $esAdmin ? 'col-xl-7' : 'col-12' }}">
            <form method="POST" action="{{ route('dashboard.sucursales.update', $sucursal) }}" id="form-datos-sucursal">
                @csrf
                <input type="hidden" name="latitud" id="latitud-envio"
                    value="{{ old('latitud', $sucursal->latitud) }}">
                <input type="hidden" name="longitud" id="longitud-envio"
                    value="{{ old('longitud', $sucursal->longitud) }}">
                <div class="card tarjeta-producto">
                    <div class="card-body">
                        <h2 class="h5 fw-bold mb-3">Datos generales</h2>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="nombre" class="form-control" required
                                    value="{{ old('nombre', $sucursal->nombre) }}" maxlength="255">
                                @error('nombre')
                                    <div class="form-text text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                                <input type="text" name="telefono" class="form-control" required
                                    value="{{ old('telefono', $sucursal->telefono) }}" maxlength="15">
                                @error('telefono')
                                    <div class="form-text text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Dirección <span class="text-danger">*</span></label>
                                <input type="text" name="direccion" class="form-control" required
                                    value="{{ old('direccion', $sucursal->direccion) }}" maxlength="255">
                                @error('direccion')
                                    <div class="form-text text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light-subtle border-top">
                        <button type="submit" class="btn btn-brand">💾 Guardar sucursal</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="{{ $esAdmin ? 'col-xl-5' : 'col-12' }}">
            <div class="card tarjeta-producto">
                <div class="card-body">
                    <h2 class="h5 fw-bold mb-1">Ubicación en el mapa</h2>
                    <p class="small text-muted mb-3">
                        Busca una dirección, haz clic en el mapa o arrastra el marcador para fijar la
                        ubicación exacta.
                    </p>

                    <div class="input-group mb-3">
                        <input type="text" id="buscarLugar" class="form-control"
                            placeholder="Buscar dirección o lugar…">
                        <button type="button" id="btnBuscarLugar" class="btn btn-brand">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>

                    <div id="mapa-editar" class="mapa-editar mb-3"></div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Latitud <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="latitud" id="latitud"
                                class="form-control" value="{{ old('latitud', $sucursal->latitud) }}"
                                required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Longitud <span class="text-danger">*</span></label>
                            <input type="number" step="any" name="longitud" id="longitud"
                                class="form-control" value="{{ old('longitud', $sucursal->longitud) }}"
                                required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
