<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Promocion;
use App\Models\PromocionSucursal;
use App\Models\Sucursal;
use App\Models\SucursalProducto;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Promociones por sucursal: una promoción (nombre y foto) se publica en una
 * sucursal con su tipo de descuento, su vigencia, los días de la semana en que
 * aplica y los productos sobre los que incide.
 */
class PromocionController extends Controller
{
    /** Tipos de descuento que permite la enumeración tipodescuento de la base. */
    private const TIPOS_DESCUENTO = ['PORCENTAJE', 'DESCUENTO_MONTO', 'PRECIO_PROMOCIONAL'];

    /**
     * Muestra las promociones de la sucursal elegida junto con los productos
     * de esa sucursal que pueden entrar en una promoción.
     */
    public function index(Request $request): View
    {
        $empleado = auth()->user()->empleado->load(['rol', 'sucursal']);

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            abort(403, 'No tienes una sucursal asignada para gestionar promociones.');
        }

        $sucursalActual = $sucursales->firstWhere('id', $request->integer('idsucursal'))
            ?? $sucursales->first();

        $busqueda = $request->string('busqueda')->trim()->toString();

        $promociones = PromocionSucursal::with(['promocion', 'dias', 'productosSucursal.producto'])
            ->where('idsucursal', $sucursalActual->id)
            ->when($busqueda !== '', fn ($query) => $query->whereHas(
                'promocion',
                fn ($promocion) => $promocion->whereRaw('lower(promocion.nombre) like ?', ['%'.mb_strtolower($busqueda).'%'])
            ))
            ->orderByDesc('activo')
            ->orderByDesc('fechainicio')
            ->paginate(10)
            ->withQueryString();

        // Productos vendidos en la sucursal y disponibles: son los que pueden
        // participar en una promoción.
        $productosDisponibles = SucursalProducto::with('producto')
            ->where('idsucursal', $sucursalActual->id)
            ->where('disponibilidad', true)
            ->orderBy(SucursalProducto::select('nombre')->from('producto')->whereColumn('producto.id', 'sucursal_producto.idproducto'))
            ->get();

        $permisos = $this->codigosPermisoActuales($empleado);

        // El selector lateral solo tiene sentido con más de una sucursal.
        $conSelectorSucursal = $sucursales->count() > 1;

        return view('admin.promociones.index', compact(
            'empleado',
            'sucursales',
            'sucursalActual',
            'promociones',
            'productosDisponibles',
            'busqueda',
        ))->with([
            'conSelectorSucursal' => $conSelectorSucursal,
            'puedeCrear' => $permisos->contains('350'),
            'puedeEditar' => $permisos->contains('351'),
            'puedeDarBaja' => $permisos->contains('355'),
        ]);
    }

    /**
     * Crea la promoción y la publica en la sucursal con sus días y productos.
     */
    public function store(Request $request): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return back()->with('error', 'No tienes una sucursal asignada para gestionar promociones.');
        }

        $validated = $request->validate($this->reglasPromocion(), $this->mensajesPromocion());

        $sucursal = $sucursales->firstWhere('id', $validated['idsucursal'] ?? null)
            ?? $sucursales->first();

        [$dias, $productos] = $this->resolverDiasYProductos($request, $sucursal->id);

        // La foto se sube fuera de la transacción: un fichero no se puede
        // deshacer con rollback, así que si algo falla después hay que borrarlo
        // a mano y si no quedan huérfanos en disco.
        $rutaFoto = $this->guardarFoto($request);

        try {
            $promocionSucursal = DB::transaction(function () use ($rutaFoto, $validated, $sucursal, $dias, $productos) {
                $promocion = Promocion::create([
                    'nombre' => $validated['nombre'],
                    'photo' => $rutaFoto,
                    'activo' => true,
                ]);

                $promocionSucursal = PromocionSucursal::create([
                    'idpromocion' => $promocion->id,
                    'idsucursal' => $sucursal->id,
                    'tipodescuento' => $validated['tipodescuento'],
                    'valordescuento' => $validated['valordescuento'],
                    'fechainicio' => $validated['fechainicio'],
                    'fechafin' => $validated['fechafin'],
                    'activo' => true,
                ]);

                $this->guardarDiasYProductos($promocionSucursal, $dias, $productos);

                return $promocionSucursal;
            });
        } catch (Throwable $e) {
            if ($rutaFoto !== null) {
                Storage::disk('public')->delete($rutaFoto);
            }

            throw $e;
        }

        $this->registrarAccion($empleado->id, 'CREATE', 'promocion_sucursal', $promocionSucursal->id, '350');

        return redirect()->route('dashboard.promociones.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "La promoción {$validated['nombre']} se publicó en {$sucursal->nombre}.");
    }

    /**
     * Actualiza el descuento, la vigencia, los días y los productos de una
     * promoción. La sucursal no se puede cambiar: se crea y se queda allí.
     */
    public function update(Request $request, PromocionSucursal $promocionSucursal): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursal = $this->sucursalesPermitidas($empleado)
            ->firstWhere('id', $promocionSucursal->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Esa promoción no pertenece a una sucursal que puedas operar.');
        }

        $validated = $request->validate($this->reglasPromocion(), $this->mensajesPromocion());

        [$dias, $productos] = $this->resolverDiasYProductos($request, $sucursal->id);

        DB::transaction(function () use ($request, $validated, $promocionSucursal, $dias, $productos) {
            $promocion = $promocionSucursal->promocion;

            $rutaFoto = $this->resolverFoto($request, $promocion);

            $promocion->update([
                'nombre' => $validated['nombre'],
                'photo' => $rutaFoto,
            ]);

            $promocionSucursal->update([
                'tipodescuento' => $validated['tipodescuento'],
                'valordescuento' => $validated['valordescuento'],
                'fechainicio' => $validated['fechainicio'],
                'fechafin' => $validated['fechafin'],
            ]);

            $this->guardarDiasYProductos($promocionSucursal, $dias, $productos);
        });

        $this->registrarAccion($empleado->id, 'UPDATE', 'promocion_sucursal', $promocionSucursal->id, '351');

        return redirect()->route('dashboard.promociones.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "La promoción {$validated['nombre']} fue actualizada correctamente.");
    }

    /**
     * Da de baja o reactiva la promoción en la sucursal. No borra nada: el
     * registro y su historial de fechas se conservan.
     */
    public function alternarActivo(PromocionSucursal $promocionSucursal): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursal = $this->sucursalesPermitidas($empleado)
            ->firstWhere('id', $promocionSucursal->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Esa promoción no pertenece a una sucursal que puedas operar.');
        }

        $activo = ! $promocionSucursal->activo;

        DB::transaction(function () use ($promocionSucursal, $activo) {
            $promocionSucursal->update(['activo' => $activo]);

            // promocion y promocion_sucursal van 1 a 1, así que se mantienen
            // sincronizadas para que la portada no tenga que mirar dos banderas.
            $promocionSucursal->promocion->update(['activo' => $activo]);
        });

        $this->registrarAccion($empleado->id, 'UPDATE', 'promocion_sucursal', $promocionSucursal->id, '355');

        $nombre = $promocionSucursal->promocion?->nombre ?? 'La promoción';

        return redirect()->route('dashboard.promociones.index', ['idsucursal' => $sucursal->id])
            ->with('exito', $activo
                ? "{$nombre} volvió a estar activa en {$sucursal->nombre}."
                : "{$nombre} dejó de estar activa en {$sucursal->nombre}.");
    }

    /**
     * Reporte PDF de los productos promocionados de una sucursal dentro de un
     * rango de fechas. Se listan las promociones cuya vigencia toca el período
     * elegido, con sus productos, precios y descuento.
     */
    public function reporte(Request $request): Response
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            abort(403, 'No tienes una sucursal asignada para gestionar promociones.');
        }

        $validated = $request->validate([
            'idsucursal' => ['nullable', 'integer'],
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
        ], [
            'fecha_hasta.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
        ]);

        $sucursal = $sucursales->firstWhere('id', $validated['idsucursal'] ?? null)
            ?? $sucursales->first();

        $desde = Carbon::parse($validated['fecha_desde'])->startOfDay();
        $hasta = Carbon::parse($validated['fecha_hasta'])->endOfDay();

        // Una promoción entra en el reporte si su vigencia toca el período en
        // algún momento: empieza antes o el mismo día que el fin del rango y
        // termina después o el mismo día que su inicio.
        $promociones = PromocionSucursal::with(['promocion', 'dias', 'productosSucursal.producto'])
            ->where('idsucursal', $sucursal->id)
            ->where('fechainicio', '<=', $hasta)
            ->where('fechafin', '>=', $desde)
            ->orderByDesc('fechainicio')
            ->get();

        $this->registrarAccion($empleado->id, 'READ', 'promocion_sucursal', null, '352');

        $generado = now();

        $totales = [
            'promociones' => $promociones->count(),
            'productos' => $promociones->sum(fn (PromocionSucursal $p): int => $p->productosSucursal->count()),
            'ahorro' => round($promociones->sum(fn (PromocionSucursal $p): float => $p->descuentoCombo()), 2),
        ];

        $pdf = Pdf::loadView('admin.promociones.pdf.reporte', compact(
            'sucursal',
            'promociones',
            'desde',
            'hasta',
            'generado',
            'empleado',
            'totales',
        ))->setPaper('a4', 'portrait');

        return $pdf->download('productos-promocionados-'.Str::slug($sucursal->nombre).'-'.$desde->format('Ymd').'.pdf');
    }

    /** Reglas comunes de promoción para crear y para editar. */
    private function reglasPromocion(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'idsucursal' => ['nullable', 'integer'],
            'tipodescuento' => ['required', 'string', Rule::in(self::TIPOS_DESCUENTO)],
            'valordescuento' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'fechainicio' => ['required', 'date'],
            'fechafin' => ['required', 'date', 'after:fechainicio'],
            'dias' => ['required', 'array', 'min:1'],
            'dias.*' => ['integer', 'min:1', 'max:7'],
            'productos' => ['required', 'array', 'min:1'],
            'cantidades' => ['nullable', 'array'],
            'cantidades.*' => ['integer', 'min:1', 'max:999'],
        ];
    }

    /** Mensajes de error personalizados para las reglas comunes. */
    private function mensajesPromocion(): array
    {
        return [
            'tipodescuento.in' => 'El tipo de descuento no es válido.',
            'valordescuento.min' => 'El valor del descuento no puede ser negativo.',
            'fechafin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'dias.min' => 'Selecciona al menos un día de la semana.',
            'dias.*.between' => 'Los días de la semana deben estar entre 1 (lunes) y 7 (domingo).',
            'productos.min' => 'Selecciona al menos un producto para la promoción.',
            'cantidades.*.min' => 'La cantidad debe ser al menos 1.',
        ];
    }

    /**
     * Valida los límites propios de cada tipo de descuento y devuelve los días
     * elegidos y la cantidad por producto seleccionado.
     *
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private function resolverDiasYProductos(Request $request, int $idsucursal): array
    {
        if ($request->input('tipodescuento') === 'PORCENTAJE'
            && (float) $request->input('valordescuento') > 100) {
            // El numeric acompaña al max: sin él, max se interpretaría como
            // longitud de texto y 150 pasaría por tener 3 caracteres.
            $request->validate(['valordescuento' => ['numeric', 'max:100']], [
                'valordescuento.max' => 'Un porcentaje no puede superar el 100 %.',
            ]);
        }

        // Cada producto seleccionado debe pertenecer a la sucursal y estar
        // disponible para venderse en ella.
        $request->validate([
            'productos.*' => [
                'integer',
                Rule::exists('sucursal_producto', 'id')
                    ->where('idsucursal', $idsucursal)
                    ->where('disponibilidad', true),
            ],
        ], [
            'productos.*.exists' => 'Hay un producto seleccionado que no está disponible en esa sucursal.',
        ]);

        $dias = collect($request->array('dias'))
            ->map(fn ($dia) => (int) $dia)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $productos = collect($request->array('productos'))
            ->mapWithKeys(fn ($id) => [
                (int) $id => max(1, (int) $request->input("cantidades.$id", 1)),
            ])
            ->all();

        return [$dias, $productos];
    }

    /**
     * Reemplaza los días y los productos de la promoción por los recién
     * elegidos, para que no queden participantes huérfanos.
     *
     * @param  array<int, int>  $dias
     * @param  array<int, int>  $productos  id de sucursal_producto => cantidad
     */
    private function guardarDiasYProductos(PromocionSucursal $promocionSucursal, array $dias, array $productos): void
    {
        $promocionSucursal->dias()->delete();
        $promocionSucursal->productosSucursal()->detach();

        foreach ($dias as $diasemana) {
            $promocionSucursal->dias()->create(['diasemana' => $diasemana]);
        }

        foreach ($productos as $idsucursalproducto => $cantidad) {
            $promocionSucursal->productosSucursal()->attach($idsucursalproducto, [
                'idsucursal' => $promocionSucursal->idsucursal,
                'cantidad_producto' => $cantidad,
            ]);
        }
    }

    /** Sube la foto al disco public y devuelve su ruta relativa. */
    private function guardarFoto(Request $request): ?string
    {
        $foto = $request->file('photo');

        if (! $foto || ! $foto->isValid()) {
            return null;
        }

        return $foto->store('promociones', 'public');
    }

    /**
     * Decide qué foto queda guardada: una subida nueva reemplaza a la
     * anterior, "quitar foto" la borra del disco y, si no se tocó nada, se
     * conserva la que ya tenía.
     */
    private function resolverFoto(Request $request, Promocion $promocion): ?string
    {
        if ($request->boolean('quitar_photo')) {
            $this->borrarFoto($promocion);

            return null;
        }

        $rutaNueva = $this->guardarFoto($request);

        if ($rutaNueva !== null) {
            $this->borrarFoto($promocion);
        }

        return $rutaNueva ?? $promocion->photo;
    }

    private function borrarFoto(Promocion $promocion): void
    {
        if ($promocion->photo !== null) {
            Storage::disk('public')->delete($promocion->photo);
        }
    }

    /**
     * Sucursales que el empleado puede operar: el administrador ve todas y el
     * gerente solo las que gerencia, igual que en inventario y productos.
     */
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
