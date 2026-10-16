<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\ConfiguracionSistema;
use App\Models\Sucursal;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {

        // obtener la configuracion del sistema
        $configuracion = ConfiguracionSistema::where('estado', true)
            ->pluck('valor', 'clave');

        // obtener el conjunto de imagenes_carrusel
        $sliders = collect(json_decode($configuracion->get('IMAGENES_CARRUSEL', '[]'), true))
            ->sortBy('orden')
            ->values();

        // relacion que me obtiene sus horarios de atencion como los productos con sus categorias por sucursal
        $sucursales = Sucursal::with([
            'horarios',
            'productosSucursal.producto.categoria',
        ])->where('estado', true)->orderBy('nombre')->get();

        // obtener las categorias activas ordenadas por nombre y para cada cat obtener cantidad de productos
        $categorias = Categoria::withCount(['productos' => function ($q) {
            $q->where('estado', true);
        }])->where('estado', true)->orderBy('nombre')->get();

        // NOTA: el modulo de promociones se encuentra en reconstruccion.
        // La logica anterior se elimino y se recreara con el nuevo modelo por sucursal.

        return view('inicio', compact(
            'configuracion',
            'sliders',
            'sucursales',
            'categorias',
        ));
    }
}
