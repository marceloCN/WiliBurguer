<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Insumo;
use App\Models\InventarioSucursal;
use App\Models\MovimientoInventario;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public const TIPOS = ['ENTRADA', 'SALIDA', 'AJUSTE'];

    public function index(Request $request): View
    {
        $empleado = auth()->user()->empleado->load(['rol.permisos', 'sucursal']);

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            abort(403, 'No tienes una sucursal asignada para operar el inventario.');
        }

        $idsucursal = $request->integer('idsucursal');

        $sucursalActual = $sucursales->firstWhere('id', $idsucursal)
            ?? $sucursales->first();

        $inventario = InventarioSucursal::with('insumo.unidadMedida')
            ->where('idsucursal', $sucursalActual->id)
            ->orderBy(Insumo::select('nombre')->whereColumn('insumo.id', 'inventariosucursal.idinsumo'))
            ->paginate(10, ['*'], 'stockPage')
            ->withQueryString();

        $hayInsumos = Insumo::where('estado', true)->exists();

        $movimientos = MovimientoInventario::with(['inventarioSucursal.insumo.unidadMedida', 'empleado'])
            ->whereHas('inventarioSucursal', fn ($query) => $query->where('idsucursal', $sucursalActual->id))
            ->orderByDesc('fechamovimiento')
            ->paginate(10, ['*'], 'movimientosPage')
            ->withQueryString();

        $claverol = $empleado->rol?->claverol;
        $permisos = $this->codigosPermisoActuales($empleado);

        return view('admin.inventario.index', compact(
            'empleado',
            'sucursales',
            'sucursalActual',
            'inventario',
            'movimientos',
        ))->with([
            'esAdmin' => $claverol === 'ADMINISTRADOR',
            'esGerente' => $claverol === 'GERENTE',
            'hayInsumos' => $hayInsumos,
            'puedeRegistrar' => $permisos->contains('346'),
            'puedeReporte' => $permisos->contains('349'),
        ]);
    }

    public function buscarInsumos(Request $request): JsonResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return response()->json(['message' => 'No tienes una sucursal asignada.'], 403);
        }

        $idsucursal = $request->integer('idsucursal');
        $sucursal = $sucursales->firstWhere('id', $idsucursal)
            ?? $sucursales->first();

        $busqueda = $request->string('q')->trim()->toString();

        $ingresados = InventarioSucursal::where('idsucursal', $sucursal->id)->pluck('idinsumo');

        $insumos = Insumo::with('unidadMedida')
            ->where('estado', true)
            ->when($busqueda, fn ($query) => $query->where('nombre', 'ilike', "%{$busqueda}%"))
            ->orderBy('nombre')
            ->limit(20)
            ->get();

        return response()->json($insumos->map(fn (Insumo $insumo): array => [
            'value' => $insumo->id,
            'text' => "{$insumo->nombre} (".($insumo->unidadMedida?->abreviatura ?? '—').')',
            'nuevo' => ! $ingresados->contains($insumo->id),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return back()->with('error', 'No tienes una sucursal asignada para operar el inventario.');
        }

        $tipoMovimiento = mb_strtoupper($request->input('tipo', ''));

        $validated = $request->validate([
            'idsucursal' => ['nullable', 'integer'],
            'idinsumo' => ['required', 'exists:insumo,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'cantidadminima' => ['nullable', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', Rule::in(self::TIPOS)],
        ]);

        $sucursal = $sucursales->firstWhere('id', $validated['idsucursal'] ?? null)
            ?? $sucursales->first();

        $insumo = Insumo::findOrFail($validated['idinsumo']);

        if (! $insumo->estado) {
            return back()->withInput()->with('error', "El insumo {$insumo->nombre} está inactivo; activalo antes de registrar movimientos.");
        }

        $inventario = InventarioSucursal::firstOrCreate(
            ['idsucursal' => $sucursal->id, 'idinsumo' => $insumo->id],
            [
                'cantidad' => 0,
                'cantidadminima' => $validated['cantidadminima'] ?? null,
                'fechaactualizacion' => now(),
            ],
        );

        $cantidadAnterior = $inventario->cantidad;
        $cantidadNueva = match ($tipoMovimiento) {
            'ENTRADA' => $cantidadAnterior + $validated['cantidad'],
            'SALIDA' => $cantidadAnterior - $validated['cantidad'],
            'AJUSTE' => $validated['cantidad'],
        };

        if ($cantidadNueva < 0) {
            return back()->withInput()->with('error', "Stock insuficiente: el insumo {$insumo->nombre} solo tiene {$this->formatear($cantidadAnterior)}.");
        }

        $inventario->update([
            'cantidad' => $cantidadNueva,
            'fechaactualizacion' => now(),
        ]);

        MovimientoInventario::create([
            'idinventariosucursal' => $inventario->id,
            'tipomovimiento' => $tipoMovimiento,
            'cantidad' => $validated['cantidad'],
            'cantidadanterior' => $cantidadAnterior,
            'cantidadnueva' => $cantidadNueva,
            'fechamovimiento' => now(),
            'idempleado' => $empleado->id,
            'observacion' => $validated['observacion'] ?? null,
        ]);

        $this->registrarAccion($empleado->id, 'CREATE', 'movimientoinventario', $inventario->id, '346');

        return redirect()->route('dashboard.inventario.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Movimiento {$tipoMovimiento} de {$this->formatear($validated['cantidad'])} registrado para {$insumo->nombre}.");
    }

    public function actualizarMinimo(Request $request, InventarioSucursal $inventario): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return back()->with('error', 'No tienes una sucursal asignada para operar el inventario.');
        }

        $sucursal = $sucursales->firstWhere('id', $inventario->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Ese insumo no pertenece a una sucursal que puedas operar.');
        }

        $validated = $request->validate([
            'cantidadminima' => ['nullable', 'numeric', 'min:0'],
        ]);

        $inventario->update([
            'cantidadminima' => $validated['cantidadminima'] === null ? null : $validated['cantidadminima'],
            'fechaactualizacion' => now(),
        ]);

        $this->registrarAccion($empleado->id, 'UPDATE', 'inventariosucursal', $inventario->id, '348');

        return redirect()->route('dashboard.inventario.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "Cantidad mínima de {$inventario->insumo?->nombre} establecida en {$this->formatear($inventario->cantidadminima)}.");
    }

    public function reporte(Request $request): Response
    {
        /** @var Empleado $empleado */
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            abort(403, 'No tienes una sucursal asignada para operar el inventario.');
        }

        $idsucursal = $request->integer('idsucursal');

        $sucursal = $sucursales->firstWhere('id', $idsucursal)
            ?? $sucursales->first();

        $tipo = $request->string('tipo', 'estado')->toString();

        return match ($tipo) {
            'movimientos' => $this->reporteMovimientos($request, $sucursal, $empleado),
            default => $this->reporteEstado($sucursal, $empleado),
        };
    }

    private function reporteEstado(Sucursal $sucursal, Empleado $empleado): Response
    {
        $inventario = InventarioSucursal::with(['insumo.unidadMedida', 'sucursal'])
            ->where('idsucursal', $sucursal->id)
            ->orderBy(Insumo::select('nombre')->whereColumn('insumo.id', 'inventariosucursal.idinsumo'))
            ->get();

        $generado = now();

        $maxCantidad = max($inventario->max('cantidad') ?? 0, 1);

        $pdf = Pdf::loadView(
            'admin.inventario.pdf.estado',
            compact('sucursal', 'inventario', 'generado', 'empleado', 'maxCantidad')
        )->setPaper('a4', 'portrait');

        return $pdf->download('inventario-estado-'.Str::slug($sucursal->nombre).'-'.$generado->format('Ymd').'.pdf');
    }

    private function reporteMovimientos(Request $request, Sucursal $sucursal, Empleado $empleado): Response
    {
        $validated = $request->validate([
            'fecha_desde' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $desde = Carbon::parse($validated['fecha_desde'])->startOfDay();
        $hasta = now()->endOfDay();

        $movimientos = MovimientoInventario::with(['inventarioSucursal.insumo.unidadMedida', 'empleado.rol'])
            ->whereHas('inventarioSucursal', fn ($query) => $query->where('idsucursal', $sucursal->id))
            ->whereBetween('fechamovimiento', [$desde, $hasta])
            ->orderBy('fechamovimiento')
            ->get();

        $generado = now();

        $totales = [
            'ENTRADA' => $movimientos->where('tipomovimiento', 'ENTRADA')->sum('cantidad'),
            'SALIDA' => $movimientos->where('tipomovimiento', 'SALIDA')->sum('cantidad'),
            'AJUSTE' => $movimientos->where('tipomovimiento', 'AJUSTE')->sum('cantidad'),
        ];

        $conteos = [
            'ENTRADA' => $movimientos->where('tipomovimiento', 'ENTRADA')->count(),
            'SALIDA' => $movimientos->where('tipomovimiento', 'SALIDA')->count(),
            'AJUSTE' => $movimientos->where('tipomovimiento', 'AJUSTE')->count(),
        ];

        $pdf = Pdf::loadView(
            'admin.inventario.pdf.movimientos',
            compact('sucursal', 'movimientos', 'desde', 'hasta', 'generado', 'totales', 'conteos', 'empleado')
        )->setPaper('a4', 'portrait');

        return $pdf->download('inventario-movimientos-'.Str::slug($sucursal->nombre).'-'.$desde->format('Ymd').'.pdf');
    }

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

    private function formatear(float|int|string|null $cantidad): string
    {
        return $cantidad === null
            ? '—'
            : rtrim(rtrim(number_format((float) $cantidad, 3, '.', ''), '0'), '.');
    }
}
