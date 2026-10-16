<div class="card tarjeta-producto mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $ruta }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="busqueda" class="form-label small fw-bold">Buscar</label>
                <div class="input-group">
                    <input type="text" name="busqueda" id="busqueda" class="form-control"
                           placeholder="Nombre, apellido, usuario o correo…"
                           value="{{ $busqueda }}">
                    <button type="submit" class="btn btn-brand">Buscar</button>
                </div>
            </div>
            @if (! $sucursalGerente)
                <div class="col-6 col-md-3">
                    <label for="idsucursal" class="form-label small fw-bold">Sucursal</label>
                    <select name="idsucursal" id="idsucursal" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected($idsucursal == $sucursal->id)>{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-6 col-md-2">
                <label for="idrol" class="form-label small fw-bold">Rol</label>
                <select name="idrol" id="idrol" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id }}" @selected($idrol == $rol->id)>{{ $rol->nombrerol }}</option>
                    @endforeach
                </select>
            </div>
            @php
                $puedeListarActivos = $permisosUsuario->contains('107');
                $puedeListarInactivos = $permisosUsuario->contains('108');
            @endphp
            <div class="col-6 col-md-2">
                <label for="estado" class="form-label small fw-bold">Estado</label>
                @if ($puedeListarActivos && $puedeListarInactivos)
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" @selected($estado === '1')>Activos</option>
                        <option value="0" @selected($estado === '0')>Inactivos</option>
                    </select>
                @elseif ($puedeListarActivos)
                    <select name="estado" id="estado" class="form-select">
                        <option value="1" @selected($estado === '1' || $estado === null || $estado === '')>Activos</option>
                    </select>
                @elseif ($puedeListarInactivos)
                    <select name="estado" id="estado" class="form-select">
                        <option value="0" @selected($estado === '0' || $estado === null || $estado === '')>Inactivos</option>
                    </select>
                @else
                    <button type="button" class="form-select text-start text-muted"
                            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Filtrar empleados por estado', '107, 108')">
                        Estado — sin permiso
                    </button>
                @endif
            </div>
            <div class="col-6 col-md-1 d-flex gap-2">
                @if ($busqueda || $idrol || $idsucursal || ($estado !== null && $estado !== ''))
                    <a href="{{ $ruta }}" class="btn btn-outline-secondary">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
</div>
