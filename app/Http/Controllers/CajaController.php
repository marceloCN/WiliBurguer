<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use App\Models\Caja;
use App\Models\CierreCaja;
use App\Models\Empleado;
use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CajaController extends Controller
{
    public const TIPOS_MOVIMIENTO = ['INGRESO', 'EGRESO'];

    /**
     * Ventana de caja: estado de las cajas de la sucursal, jornada en curso
     * (abierta por el empleado), movimientos de la jornada e historial de
     * aperturas con su cierre.
     *
     * Los totales del cierre los calcula la BD (trg_calcular_parametros_cierre);
     * este módulo solo escribe monto, efectivo contado y observación.
     */
    public function index(Request $request): View
    {
        $empleado = auth()->user()->empleado->load('rol');
        $rol = $empleado->rol;

        $sucursales = $this->sucursalesPermitidas($empleado);

        $sucursalActual = $sucursales->firstWhere('id', $request->integer('idsucursal'))
            ?? $sucursales->first();

        abort_unless($sucursalActual, 403, 'No tienes una sucursal asignada para operar la caja.');

        $cajas = Caja::with(['aperturasCaja' => fn ($query) => $query
            ->where('estado', 'ABIERTA')
            ->with(['empleado', 'cierreCaja'])
            ->latest('fechaapertura')])
            ->where('idsucursal', $sucursalActual->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $miTurno = AperturaCaja::with(['caja', 'cierreCaja'])
            ->where('estado', 'ABIERTA')
            ->where('idempleado', $empleado->id)
            ->whereHas('caja', fn ($query) => $query->where('idsucursal', $sucursalActual->id))
            ->latest('fechaapertura')
            ->first();

        $cajasLibres = $cajas->filter(fn (Caja $caja) => $caja->aperturasCaja->isEmpty());

        $movimientos = $miTurno
            ? MovimientoCaja::with('empleado')
                ->where('idaperturacaja', $miTurno->id)
                ->orderByDesc('fechamovimiento')
                ->paginate(10, ['*'], 'movimientosPage')
                ->withQueryString()
            : collect();

        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');
        $idcaja = $request->integer('idcaja') ?: null;

        // Cajas de la sucursal para el filtro del historial. Se listan todas
        // (incluidas las inactivas) para poder filtrar también jornadas pasadas
        // de una caja que ya fue desactivada.
        $cajasHistorial = Caja::where('idsucursal', $sucursalActual->id)
            ->orderBy('nombre')
            ->get();

        $historial = AperturaCaja::with(['caja', 'empleado', 'cierreCaja.empleado'])
            ->whereHas('caja', fn ($query) => $query->where('idsucursal', $sucursalActual->id))
            ->when($idcaja, fn ($query) => $query->where('idcaja', $idcaja))
            ->when($fechaDesde, fn ($query) => $query->whereDate('fechaapertura', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($query) => $query->whereDate('fechaapertura', '<=', $fechaHasta))
            ->orderByDesc('fechaapertura')
            ->paginate(10, ['*'], 'aperturasPage')
            ->withQueryString();

        $permisos = $this->codigosPermisoActuales($empleado);

        return view('admin.caja.index', compact(
            'empleado',
            'sucursales',
            'sucursalActual',
            'cajas',
            'cajasHistorial',
            'cajasLibres',
            'miTurno',
            'movimientos',
            'historial',
            'fechaDesde',
            'fechaHasta',
            'idcaja',
        ))->with([
            'claverol' => $rol->claverol,
            'puedeAbrir' => $permisos->contains('410'),
            'puedeCerrar' => $permisos->contains('430'),
            'puedeMovimiento' => $permisos->contains('420'),
            'puedeBajaMovimiento' => $permisos->contains('425'),
            'puedeHistorial' => $permisos->contains('412') || $permisos->contains('437'),
            'puedeVerMovimientos' => $permisos->contains('422'),
            'puedeReporteCierre' => $permisos->contains('436'),
        ]);
    }

    /**
     * Abre una jornada de caja. La BD valida que la caja no tenga otra jornada
     * abierta y crea el cierre provisional del turno.
     */
    public function store(Request $request): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return back()->with('error', 'No tienes una sucursal asignada para operar la caja.');
        }

        $validated = $request->validate([
            'idsucursal' => ['nullable', 'integer'],
            'idcaja' => ['required', 'exists:caja,id'],
            'montoinicial' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ]);

        $sucursal = $sucursales->firstWhere('id', $validated['idsucursal'] ?? null)
            ?? $sucursales->first();

        $caja = Caja::findOrFail($validated['idcaja']);

        if ($caja->idsucursal !== $sucursal->id) {
            return back()->with('error', 'Esa caja no pertenece a una sucursal que puedas operar.');
        }

        if (! $caja->activo) {
            return back()->with('error', "La caja {$caja->nombre} está inactiva; actívala antes de abrir una jornada.");
        }

        if ($this->cajaTieneJornadaAbierta($caja->id)) {
            return back()->with('error', "La caja {$caja->nombre} ya tiene una jornada abierta; ciérrala antes de abrir una nueva.");
        }

        try {
            $apertura = AperturaCaja::create([
                'fechaapertura' => now(),
                'montoinicial' => $validated['montoinicial'],
                'estado' => 'ABIERTA',
                'idcaja' => $caja->id,
                'idempleado' => $empleado->id,
            ]);
        } catch (QueryException) {
            return back()->with('error', "La caja {$caja->nombre} ya tiene una jornada abierta; ciérrala antes de abrir una nueva.");
        }

        $this->registrarAccion($empleado->id, 'CREATE', 'aperturacaja', $apertura->id, '410');

        return redirect()->route('dashboard.caja.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Caja {$caja->nombre} abierta con un fondo inicial de Bs. ".number_format($validated['montoinicial'], 2).'.');
    }

    /**
     * Cierra la jornada y su cierre. Solo se envía el efectivo contado y la
     * observación: el trigger recalcula totales y diferencia.
     */
    public function cerrar(Request $request, AperturaCaja $apertura): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        $sucursal = $sucursales->firstWhere('id', $apertura->caja?->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Esa jornada de caja no pertenece a una sucursal que puedas operar.');
        }

        if ($apertura->estado !== 'ABIERTA') {
            return back()->with('error', 'Esa jornada de caja ya estaba cerrada.');
        }

        $validated = $request->validate([
            'efectivocontado' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $cierre = CierreCaja::firstOrCreate(
            ['idaperturacaja' => $apertura->id],
            ['idempleado' => $empleado->id],
        );

        $cierre->update([
            'fechacierre' => now(),
            'efectivocontado' => $validated['efectivocontado'],
            'observacion' => $validated['observacion'] ?? null,
            'idempleado' => $empleado->id,
        ]);

        $apertura->update(['estado' => 'CERRADA']);

        $this->registrarAccion($empleado->id, 'UPDATE', 'cierrecaja', $cierre->id, '430');
        $this->registrarAccion($empleado->id, 'UPDATE', 'aperturacaja', $apertura->id, '430');

        return redirect()->route('dashboard.caja.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Jornada de la caja {$apertura->caja?->nombre} cerrada. Diferencia: Bs. ".number_format($cierre->fresh()?->diferencia ?? 0, 2).'.');
    }

    /**
     * PDF de cierre de una jornada ya cerrada: quién abrió y quién cerró,
     * fondos y totales del cierre, ventas de la jornada con su detalle y los
     * movimientos de caja que se registraron en ella.
     *
     * Las ventas entran por los pagos aprobados de la apertura (es el mismo
     * criterio que usa el trigger del cierre), y cada pago trae su venta, su
     * pedido y las líneas de producto o combo que lo componen.
     */
    public function reporte(AperturaCaja $apertura): Response
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        $sucursal = $sucursales->firstWhere('id', $apertura->caja?->idsucursal);

        if (! $sucursal) {
            abort(403, 'Esa jornada de caja no pertenece a una sucursal que puedas consultar.');
        }

        if ($apertura->estado === 'ABIERTA') {
            // Sin cierre no hay cifras que reportar: la BD recién las calcula
            // al cerrar la jornada.
            abort(403, 'Solo se puede generar el reporte de una jornada cerrada.');
        }

        $apertura->load([
            'caja.sucursal',
            'empleado.rol',
            'cierreCaja.empleado.rol',
            'movimientosCaja.empleado',
            'pagos.metodoPago',
            'pagos.venta.empleado',
            'pagos.venta.pedido.mesa',
            'pagos.venta.pedido.detalles.sucursalProducto.producto',
            'pagos.venta.pedido.detalles.promocionSucursal.promocion',
        ]);

        $cierre = $apertura->cierreCaja;

        if (! $cierre) {
            abort(403, 'Esa jornada de caja no tiene cierre registrado.');
        }

        $pagos = $apertura->pagos
            ->where('estadopago', 'APROBADO')
            ->sortBy('fechapago')
            ->values();

        $ventas = $pagos->groupBy('idventa')->map(fn ($pagosDeLaVenta) => $pagosDeLaVenta->first()->venta)->values();

        // Siempre se listan los tres métodos, aunque uno no tenga cobros: un
        // cierre de caja se cuadra revisando cada método, no solo los usados.
        $totalesPorMetodo = collect(['Efectivo' => 0, 'QR' => 0, 'Tarjeta de crédito' => 0]);

        foreach ($pagos as $pago) {
            $totalesPorMetodo[$this->metodoDeCierre($pago)] += (float) $pago->montopago;
        }

        $movimientos = $apertura->movimientosCaja->sortBy('fechamovimiento')->values();

        $totalesMovimientos = [
            'INGRESO' => (float) $movimientos->where('tipomovimiento', 'INGRESO')->sum('monto'),
            'EGRESO' => (float) $movimientos->where('tipomovimiento', 'EGRESO')->sum('monto'),
        ];

        $generado = now();

        $pdf = Pdf::loadView('admin.caja.pdf.cierre', compact(
            'sucursal',
            'apertura',
            'cierre',
            'pagos',
            'ventas',
            'totalesPorMetodo',
            'movimientos',
            'totalesMovimientos',
            'empleado',
            'generado',
        ))->setPaper('a4', 'portrait');

        $this->registrarAccion($empleado->id, 'READ', 'aperturacaja', $apertura->id, '436');

        return $pdf->download(
            'cierre-caja-'.Str::slug($apertura->caja?->nombre ?? 'caja').'-'.$apertura->fechaapertura->format('Ymd').'.pdf'
        );
    }

    /** Registra un ingreso o egreso de la jornada en curso. */
    public function movimientoStore(Request $request): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return back()->with('error', 'No tienes una sucursal asignada para operar la caja.');
        }

        $validated = $request->validate([
            'idaperturacaja' => ['required', 'exists:aperturacaja,id'],
            'tipomovimiento' => ['required', Rule::in(self::TIPOS_MOVIMIENTO)],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'concepto' => ['required', 'string', 'max:255'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ]);

        $apertura = AperturaCaja::with('caja')->findOrFail($validated['idaperturacaja']);

        $sucursal = $sucursales->firstWhere('id', $apertura->caja?->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Esa jornada de caja no pertenece a una sucursal que puedas operar.');
        }

        if ($apertura->estado !== 'ABIERTA') {
            return back()->with('error', 'Solo se pueden registrar movimientos en una jornada abierta.');
        }

        MovimientoCaja::create([
            'fechamovimiento' => now(),
            'tipomovimiento' => $validated['tipomovimiento'],
            'concepto' => $validated['concepto'],
            'monto' => $validated['monto'],
            'observacion' => $validated['observacion'] ?? null,
            'idaperturacaja' => $apertura->id,
            'idempleado' => $empleado->id,
        ]);

        $this->registrarAccion($empleado->id, 'CREATE', 'movimientocaja', $apertura->id, '420');

        return redirect()->route('dashboard.caja.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "{$validated['tipomovimiento']} de Bs. ".number_format($validated['monto'], 2).' registrado.');
    }

    /** Da de baja un movimiento mientras la jornada siga abierta. */
    public function movimientoBaja(MovimientoCaja $movimiento): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        $apertura = $movimiento->aperturaCaja()->with('caja')->first();

        $sucursal = $sucursales->firstWhere('id', $apertura?->caja?->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Ese movimiento no pertenece a una sucursal que puedas operar.');
        }

        if ($apertura->estado !== 'ABIERTA') {
            return back()->with('error', 'Solo se pueden dar de baja movimientos de una jornada abierta.');
        }

        $movimiento->delete();

        $this->registrarAccion($empleado->id, 'DELETE', 'movimientocaja', $movimiento->id, '425');

        return redirect()->route('dashboard.caja.index', ['idsucursal' => $sucursal->id])
            ->with('exito', 'Movimiento dado de baja. El cierre de la jornada se actualizó automáticamente.');
    }

    private function cajaTieneJornadaAbierta(int $idcaja): bool
    {
        return AperturaCaja::where('idcaja', $idcaja)
            ->where('estado', 'ABIERTA')
            ->exists();
    }

    /**
     * Agrupa un pago en el método que reporta el cierre. Reproduce la regla de
     * fn_calcular_parametros_cierre: 'efectivo' entra en efectivo, lo que
     * contiene 'tarjeta' en tarjeta y todo lo demás (QR, transferencia) en QR.
     */
    private function metodoDeCierre(Pago $pago): string
    {
        $nombre = mb_strtolower($pago->metodoPago?->nombre ?? '');

        if ($nombre === 'efectivo') {
            return 'Efectivo';
        }

        return str_contains($nombre, 'tarjeta') ? 'Tarjeta de crédito' : 'QR';
    }

    /** Sucursales que el empleado puede operar (misma regla que inventario/pedidos). */
    private function sucursalesPermitidas(Empleado $empleado): Collection
    {
        $claverol = $empleado->rol?->claverol;

        if ($claverol === 'ADMINISTRADOR') {
            return Sucursal::orderBy('nombre')->get();
        }

        if ($claverol === 'GERENTE') {
            return $empleado->sucursalesGerenciadas()
                ->where('estado', true)
                ->orderBy('nombre')
                ->get();
        }

        return $empleado->idsucursal
            ? Sucursal::where('id', $empleado->idsucursal)->get()
            : collect();
    }
}
