<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\DetalleReceta;
use App\Models\Insumo;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Recetas de productos: vincula cada producto con los insumos y las
 * cantidades necesarios para prepararlo.
 */
class RecetaController extends Controller
{
    /**
     * Lista los productos con el numero de insumos de su receta y prepara el
     * detalle de cada producto de la pagina para el panel lateral.
     */
    public function index(Request $request): View
    {
        $busqueda = $request->string('busqueda')->trim()->toString();
        $idcategoria = $request->integer('idcategoria');

        $categorias = Categoria::where('estado', true)
            ->orderBy('nombre')
            ->get();

        $productos = Producto::with(['categoria', 'detallesReceta.insumo.unidadMedida'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->whereRaw('lower(nombre) like ?', ['%'.mb_strtolower($busqueda).'%']);
            })
            ->when($idcategoria > 0, function ($query) use ($idcategoria) {
                $query->where('idcategoria', $idcategoria);
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        // Insumos activos más los insumos que ya forman parte de alguna receta
        // de la pagina (aunque se hayan desactivado, para poder editarlos).
        $insumosReceta = $productos->getCollection()
            ->flatMap(fn (Producto $producto) => $producto->detallesReceta)
            ->pluck('idinsumo')
            ->unique();

        $insumos = Insumo::with('unidadMedida')
            ->where(fn ($query) => $query
                ->where('estado', true)
                ->orWhereIn('id', $insumosReceta))
            ->orderBy('nombre')
            ->get();

        $recetasPorProducto = $productos
            ->getCollection()
            ->mapWithKeys(fn (Producto $producto) => [
                $producto->id => [
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria?->nombre,
                    'detalles' => $producto->detallesReceta
                        ->map(fn (DetalleReceta $detalle) => [
                            'iddetalle' => $detalle->id,
                            'idproducto' => $detalle->idproducto,
                            'idinsumo' => $detalle->idinsumo,
                            'insumo' => $detalle->insumo?->nombre,
                            'unidad' => $detalle->insumo?->unidadMedida?->abreviatura,
                            'cantidad' => (string) $detalle->cantidad,
                        ])
                        ->values()
                        ->all(),
                ],
            ])
            ->all();

        $permisos = $this->codigosPermisoActuales();

        return view('admin.recetas.index', compact(
            'productos',
            'insumos',
            'recetasPorProducto',
            'categorias',
            'busqueda',
            'idcategoria',
        ))->with([
            'puedeCrear' => $permisos->contains('340'),
            'puedeEditar' => $permisos->contains('341'),
            'puedeBaja' => $permisos->contains('345'),
            'puedeVerInsumos' => $permisos->contains('330') && $permisos->contains('336'),
        ]);
    }

    /**
     * Agrega un insumo a la receta del producto indicado.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idproducto' => ['required', 'integer', Rule::exists('producto', 'id')],
            'idinsumo' => [
                'required',
                'integer',
                Rule::exists('insumo', 'id')->where('estado', true),
                Rule::unique('detallereceta', 'idinsumo')
                    ->where('idproducto', $request->integer('idproducto')),
            ],
            'cantidad' => ['required', 'numeric', 'gt:0', 'lte:999999999.999'],
        ], [
            'idproducto.exists' => 'El producto seleccionado ya no existe.',
            'idinsumo.exists' => 'El insumo seleccionado no existe o está desactivado.',
            'idinsumo.unique' => 'Ese insumo ya forma parte de la receta de este producto.',
            'cantidad.gt' => 'La cantidad debe ser mayor que 0.',
        ]);

        $detalleReceta = DetalleReceta::create([
            'idproducto' => $validated['idproducto'],
            'idinsumo' => $validated['idinsumo'],
            'cantidad' => $validated['cantidad'],
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'detallereceta', $detalleReceta->id, '340');

        return $this->volverALaReceta($detalleReceta->idproducto)
            ->with('exito', 'El insumo se agregó a la receta correctamente.');
    }

    /**
     * Modifica el insumo o la cantidad de un paso de la receta.
     * El producto se toma del registro, nunca del formulario.
     */
    public function update(Request $request, DetalleReceta $detalleReceta): RedirectResponse
    {
        $validated = $request->validate([
            'idinsumo' => [
                'required',
                'integer',
                Rule::exists('insumo', 'id')->where('estado', true),
                Rule::unique('detallereceta', 'idinsumo')
                    ->where('idproducto', $detalleReceta->idproducto)
                    ->ignore($detalleReceta->id),
            ],
            'cantidad' => ['required', 'numeric', 'gt:0', 'lte:999999999.999'],
        ], [
            'idinsumo.exists' => 'El insumo seleccionado no existe o está desactivado.',
            'idinsumo.unique' => 'Ese insumo ya forma parte de la receta de este producto.',
            'cantidad.gt' => 'La cantidad debe ser mayor que 0.',
        ]);

        $detalleReceta->update([
            'idinsumo' => $validated['idinsumo'],
            'cantidad' => $validated['cantidad'],
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'detallereceta', $detalleReceta->id, '341');

        return $this->volverALaReceta($detalleReceta->idproducto)
            ->with('exito', 'El detalle de la receta se actualizó correctamente.');
    }

    /**
     * Da de baja un insumo de la receta
     */
    public function destroy(DetalleReceta $detalleReceta): RedirectResponse
    {
        $idProducto = $detalleReceta->idproducto;
        $nombreInsumo = $detalleReceta->insumo?->nombre ?? 'El insumo';

        $detalleReceta->delete();

        $this->registrarAccion(auth()->user()->empleado->id, 'DELETE', 'detallereceta', $detalleReceta->id, '345');

        return $this->volverALaReceta($idProducto)
            ->with('exito', "{$nombreInsumo} se quitó de la receta.");
    }

    /**
     * Regresa al listado marcando el producto cuyo panel debe reabrirse, de
     * modo que la receta actualizada  se vea alli.
     */
    private function volverALaReceta(int $idProducto): RedirectResponse
    {
        return redirect()->back()->with('producto_abierto', $idProducto);
    }
}
