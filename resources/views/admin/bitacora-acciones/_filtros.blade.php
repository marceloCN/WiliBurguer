{{--
    Filtros para la bitácora de acciones.
    Incluye usuario, sucursal, rango de fechas y botones para filtrar,
    limpiar y generar PDF.
--}}
<form id="form-filtros-acciones" method="GET" action="{{ route('dashboard.bitacora-acciones.index') }}" class="row g-2 align-items-end">
    <div class="col-12 col-md-3">
        <label for="idempleado" class="form-label small fw-bold">Usuario</label>
        <select name="idempleado" id="idempleado" class="form-select">
            <option value="">{{ $idsucursal ? 'Todos los usuarios de la sucursal' : 'Todos' }}</option>
            @foreach ($usuarios as $empleado)
                <option value="{{ $empleado->id }}" @selected($idempleado == $empleado->id)>
                    {{ $empleado->nombre }} {{ $empleado->apellido }} ({{ $empleado->cuentaUsuario?->usuario }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-3">
        <label for="idsucursal" class="form-label small fw-bold">Sucursal</label>
        <select name="idsucursal" id="idsucursal" class="form-select">
            <option value="">Todas</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}" @selected($idsucursal == $sucursal->id)>
                    {{ $sucursal->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label for="fecha_desde" class="form-label small fw-bold">Desde</label>
        <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" value="{{ $fechaDesde }}">
    </div>
    <div class="col-6 col-md-2">
        <label for="fecha_hasta" class="form-label small fw-bold">Hasta</label>
        <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" value="{{ $fechaHasta }}">
    </div>
    <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-brand">Filtrar</button>
        @if ($idempleado || $idsucursal || $fechaDesde || $fechaHasta)
            <a href="{{ route('dashboard.bitacora-acciones.index') }}" class="btn btn-outline-secondary">Limpiar</a>
        @endif
    </div>
    <div class="col-12">
        <div class="form-text">
            Al elegir una <strong>sucursal</strong>, el menú de <strong>usuario</strong> se rellena con los usuarios de esa sucursal.
            El PDF respeta los filtros activos de usuario, sucursal y fechas.
        </div>
    </div>
</form>

<div class="mt-3">
    @if ($permisosUsuario->contains('153'))
        <button type="button" class="btn btn-outline-danger" id="btn-pdf-acciones">
            Generar PDF
        </button>
    @else
        <button type="button" class="btn btn-outline-danger"
            onclick="UtilidadesFormulario.mostrarModalPermisoDenegado('Crear PDF de bitácora de acciones', '153')">
            Generar PDF
        </button>
    @endif
</div>
