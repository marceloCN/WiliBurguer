<?php

namespace App\Http\Controllers;

use App\Models\BitacoraAcceso;
use App\Models\Empleado;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BitacoraAccesoController extends Controller
{
    /**
     * Muestra la bitácora de accesos de todos los usuarios (solo ADMINISTRADOR).
     * Permite filtrar por usuario, sucursal y rango de fechas; ordena de la
     * fecha más reciente a la más antigua y pagina de 10 en 10.
     */
    public function index(Request $request): View
    {
        $accesos = $this->consultaFiltrada($request)
            ->orderByDesc('fechahora')
            ->paginate(10)
            ->withQueryString();

        $datosVista = $this->datosFiltros($request);
        $datosVista['permisosUsuario'] = $this->codigosPermisoActuales();

        return view('admin.bitacora-accesos.index', array_merge(
            compact('accesos'),
            $datosVista
        ));
    }

    /**
     * Genera y descarga un PDF con la bitácora de accesos aplicando los
     * mismos filtros de la vista: usuario, sucursal y rango de fechas.
     */
    public function pdf(Request $request)
    {
        $accesos = $this->consultaFiltrada($request)
            ->orderByDesc('fechahora')
            ->get();

        $datosVista = $this->datosFiltros($request);

        $filtros = [
            'empleado' => $request->input('idempleado')
                ? Empleado::find($request->input('idempleado'))?->nombre.' '.Empleado::find($request->input('idempleado'))?->apellido
                : null,
            'sucursal' => $request->input('idsucursal')
                ? Sucursal::find($request->input('idsucursal'))?->nombre
                : null,
            'desde' => $request->input('fecha_desde'),
            'hasta' => $request->input('fecha_hasta'),
        ];

        $pdf = Pdf::loadView('admin.bitacora-accesos.pdf', array_merge(
            compact('accesos', 'filtros'),
            $datosVista
        ));

        $this->registrarAccion(auth()->user()->empleado->id, 'READ', 'bitacoraacceso', null, '152');

        return $pdf->download('bitacora-accesos.pdf');
    }

    /**
     * Construye la consulta base de bitácora de accesos aplicando los
     * filtros recibidos por request.
     */
    private function consultaFiltrada(Request $request)
    {
        $idempleado = $request->input('idempleado');
        $idsucursal = $request->input('idsucursal');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        return BitacoraAcceso::with(['empleado.cuentaUsuario', 'empleado.sucursal', 'empleado.rol'])
            ->when($idempleado, fn ($query) => $query->where('idempleado', $idempleado))
            ->when($idsucursal, function ($query, string $idsucursal) {
                $query->whereHas('empleado', fn ($q) => $q->where('idsucursal', $idsucursal));
            })
            ->when($fechaDesde, fn ($query) => $query->whereDate('fechahora', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fechahora', '<=', $fechaHasta));
    }

    /**
     * Devuelve los datos comunes que alimentan los filtros de la vista y
     * del PDF: usuarios, sucursales, catálogo plano y valores seleccionados.
     */
    private function datosFiltros(Request $request): array
    {
        $idempleado = $request->input('idempleado');
        $idsucursal = $request->input('idsucursal');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        // Catálogo completo de usuarios con cuenta (alimenta el menú dependiente).
        $todosLosUsuarios = Empleado::with('cuentaUsuario')
            ->whereHas('cuentaUsuario')
            ->orderBy('nombre')
            ->get();

        // El filtro de usuario depende de la sucursal elegida.
        $usuarios = $idsucursal
            ? $todosLosUsuarios->where('idsucursal', (int) $idsucursal)
            : $todosLosUsuarios;

        // Catálogo plano para el menú dependiente (JavaScript de la vista).
        $catalogoUsuarios = $todosLosUsuarios->map(fn ($empleado) => [
            'id' => $empleado->id,
            'idsucursal' => $empleado->idsucursal,
            'label' => trim($empleado->nombre.' '.$empleado->apellido).' ('.$empleado->cuentaUsuario?->usuario.')',
        ])->values();

        $sucursales = Sucursal::orderBy('nombre')->get();

        return compact('usuarios', 'sucursales', 'catalogoUsuarios', 'idempleado', 'idsucursal', 'fechaDesde', 'fechaHasta');
    }
}
