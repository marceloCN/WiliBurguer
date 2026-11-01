<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Empleado;
use App\Models\HistorialProducto;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\SucursalProducto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Productos por sucursal: asigna cada producto del catálogo a una sucursal
 * con el precio con el que se vende allí y su disponibilidad, y conserva el
 * historial de los cambios de precio.
 */
class ProductoSucursalController extends Controller
{
    /**
     * Muestra los productos asignados a la sucursal elegida y los productos
     * del catálogo que aún no están asignados. El historial de precios de cada
     * producto se consulta con la ruta "historial".
     */
    public function index(Request $request): View
    {
        $empleado = auth()->user()->empleado->load(['rol', 'sucursal']);

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            abort(403, 'No tienes una sucursal asignada para gestionar productos.');
        }

        $sucursalActual = $sucursales->firstWhere('id', $request->integer('idsucursal'))
            ?? $sucursales->first();

        $busqueda = $request->string('busqueda')->trim()->toString();
        $idcategoria = $request->integer('idcategoria');

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $productosSucursal = SucursalProducto::with('producto.categoria')
            ->where('idsucursal', $sucursalActual->id)
            ->when($busqueda !== '', fn ($query) => $query->whereHas(
                'producto',
                fn ($producto) => $producto->whereRaw('lower(producto.nombre) like ?', ['%'.mb_strtolower($busqueda).'%'])
            ))
            ->when($idcategoria > 0, fn ($query) => $query->whereHas(
                'producto',
                fn ($producto) => $producto->where('idcategoria', $idcategoria)
            ))
            ->orderBy(Producto::select('nombre')->whereColumn('producto.id', 'sucursal_producto.idproducto'))
            ->paginate(10, ['*'], 'productosPage')
            ->withQueryString();

        // Productos activos que todavía no se venden en la sucursal, para el
        // selector del formulario de asignación.
        $productosDisponibles = Producto::with('categoria')
            ->where('estado', true)
            ->whereNotIn('id', $productosSucursal->getCollection()->pluck('idproducto'))
            ->when($busqueda !== '', fn ($query) => $query->whereRaw('lower(producto.nombre) like ?', ['%'.mb_strtolower($busqueda).'%']))
            ->when($idcategoria > 0, fn ($query) => $query->where('idcategoria', $idcategoria))
            ->orderBy('nombre')
            ->get();

        $permisos = $this->codigosPermisoActuales($empleado);

        // El selector lateral solo tiene sentido cuando hay más de una sucursal
        // operable; con una sola se muestra simplemente el nombre.
        $conSelectorSucursal = $sucursales->count() > 1;

        return view('admin.productos-sucursal.index', compact(
            'empleado',
            'sucursales',
            'sucursalActual',
            'categorias',
            'productosSucursal',
            'productosDisponibles',
            'busqueda',
            'idcategoria',
        ))->with([
            'conSelectorSucursal' => $conSelectorSucursal,
            'puedeRegistrar' => $permisos->contains('316'),
            'puedeEditarPrecio' => $permisos->contains('318'),
            'puedeDisponibilidad' => $permisos->contains('319'),
            'puedeVerHistorial' => $permisos->contains('320'),
        ]);
    }

    /**
     * Asigna un producto del catálogo a la sucursal con su precio de venta.
     */
    public function store(Request $request): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursales = $this->sucursalesPermitidas($empleado);

        if ($sucursales->isEmpty()) {
            return back()->with('error', 'No tienes una sucursal asignada para gestionar productos.');
        }

        $validated = $request->validate([
            'idsucursal' => ['nullable', 'integer'],
            'idproducto' => [
                'required',
                'integer',
                Rule::exists('producto', 'id')->where('estado', true),
            ],
            'precioproducto' => ['required', 'numeric', 'min:0', 'lte:99999999.99'],
        ], [
            'idproducto.exists' => 'El producto seleccionado ya no existe o está desactivado.',
            'precioproducto.min' => 'El precio no puede ser negativo.',
        ]);

        $sucursal = $sucursales->firstWhere('id', $validated['idsucursal'] ?? null)
            ?? $sucursales->first();

        $producto = Producto::findOrFail($validated['idproducto']);

        $yaAsignado = SucursalProducto::where('idsucursal', $sucursal->id)
            ->where('idproducto', $producto->id)
            ->exists();

        if ($yaAsignado) {
            return back()->withInput()->with('error', "{$producto->nombre} ya está asignado a {$sucursal->nombre}; edita su precio desde la tabla.");
        }

        $productoSucursal = SucursalProducto::create([
            'idsucursal' => $sucursal->id,
            'idproducto' => $producto->id,
            'precioproducto' => $validated['precioproducto'],
            'disponibilidad' => true,
        ]);

        $this->registrarAccion($empleado->id, 'CREATE', 'sucursal_producto', $productoSucursal->id, '316');

        return redirect()->route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "{$producto->nombre} se agregó a {$sucursal->nombre} con un precio de Bs. {$validated['precioproducto']}.");
    }

    public function actualizarPrecio(Request $request, SucursalProducto $productoSucursal): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursal = $this->sucursalesPermitidas($empleado)
            ->firstWhere('id', $productoSucursal->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Ese producto no pertenece a una sucursal que puedas operar.');
        }

        $validated = $request->validate([
            'precioproducto' => ['required', 'numeric', 'min:0', 'lte:99999999.99'],
        ], [
            'precioproducto.min' => 'El precio no puede ser negativo.',
        ]);

        $nombre = $productoSucursal->producto?->nombre ?? 'El producto';
        $precioAnterior = $productoSucursal->precioproducto;
        $precioNuevo = number_format((float) $validated['precioproducto'], 2, '.', '');

        if ((float) $precioAnterior === (float) $precioNuevo) {
            return redirect()->route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursal->id])
                ->with('exito', "El precio de {$nombre} se mantiene en Bs. {$precioNuevo}; no se registró ningún cambio.");
        }

        $productoSucursal->update(['precioproducto' => $precioNuevo]);

        HistorialProducto::create([
            'fecha_registro' => now(),
            'precioanterior' => $precioAnterior,
            'precionuevo' => $precioNuevo,
            'idproductosucursal' => $productoSucursal->id,
        ]);

        $this->registrarAccion($empleado->id, 'UPDATE', 'sucursal_producto', $productoSucursal->id, '318');

        return redirect()->route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursal->id])
            ->with('exito', "El precio de {$nombre} en {$sucursal->nombre} pasó de Bs. {$precioAnterior} a Bs. {$precioNuevo}.");
    }

    /**
     * Marca o desmarca un producto como disponible para vender en la sucursal.
     * Solo cambia la disponibilidad: el registro y su historial se conservan.
     */
    public function alternarDisponibilidad(SucursalProducto $productoSucursal): RedirectResponse
    {
        $empleado = auth()->user()->empleado->load('rol');

        $sucursal = $this->sucursalesPermitidas($empleado)
            ->firstWhere('id', $productoSucursal->idsucursal);

        if (! $sucursal) {
            return back()->with('error', 'Ese producto no pertenece a una sucursal que puedas operar.');
        }

        $disponible = ! $productoSucursal->disponibilidad;
        $productoSucursal->update(['disponibilidad' => $disponible]);

        $this->registrarAccion($empleado->id, 'UPDATE', 'sucursal_producto', $productoSucursal->id, '319');

        $nombre = $productoSucursal->producto?->nombre ?? 'El producto';

        return redirect()->route('dashboard.productos-sucursal.index', ['idsucursal' => $sucursal->id])
            ->with('exito', $disponible
                ? "{$nombre} ya está disponible en {$sucursal->nombre}."
                : "{$nombre} dejó de estar disponible en {$sucursal->nombre}.");
    }

    public function historial(SucursalProducto $productoSucursal): View
    {
        $empleado = auth()->user()->empleado->load(['rol', 'sucursal']);

        $sucursal = $this->sucursalesPermitidas($empleado)
            ->firstWhere('id', $productoSucursal->idsucursal);

        if (! $sucursal) {
            abort(403, 'Ese producto no pertenece a una sucursal que puedas operar.');
        }

        $historial = HistorialProducto::where('idproductosucursal', $productoSucursal->id)
            ->orderByDesc('fecha_registro')
            ->get();

        return view('admin.productos-sucursal._historial', compact('historial', 'productoSucursal', 'sucursal'));
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
}
