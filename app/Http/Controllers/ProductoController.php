<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Catálogo global de productos y categorías.
 *
 * El precio no vive aquí: cada sucursal fija el suyo en el módulo de productos
 * por sucursal (tabla sucursal_producto). La foto se guarda en el disco public
 * bajo productos/ y las vistas la sirven con asset('storage/…').
 */
class ProductoController extends Controller
{
    /**
     * Lista el catálogo de productos junto a las categorías, que es lo que
     * permite crear un producto nuevo.
     */
    public function index(Request $request): View
    {
        $busqueda = $request->string('busqueda')->trim()->toString();
        $idcategoria = $request->integer('idcategoria');

        $categorias = Categoria::orderBy('nombre')->get();

        $productos = Producto::with('categoria')
            ->when($busqueda !== '', fn ($query) => $query->whereRaw(
                'lower(producto.nombre) like ?',
                ['%'.mb_strtolower($busqueda).'%']
            ))
            ->when($idcategoria > 0, fn ($query) => $query->where('idcategoria', $idcategoria))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        // Categorías que ya tienen al menos un producto activo: no se pueden
        // desactivar mientras sigan en uso.
        $categoriasEnUso = Producto::where('estado', true)
            ->pluck('idcategoria')
            ->unique()
            ->all();

        $empleado = auth()->user()->empleado->load('rol.permisos');
        $permisos = $this->codigosPermisoActuales($empleado);

        return view('admin.productos.index', compact(
            'productos',
            'categorias',
            'categoriasEnUso',
            'busqueda',
            'idcategoria',
        ))->with([
            'puedeCrearProducto' => $permisos->contains('310'),
            'puedeEditarProducto' => $permisos->contains('311'),
            'puedeDesactivarProducto' => $permisos->contains('315'),
            'puedeCrearCategoria' => $permisos->contains('300'),
            'puedeEditarCategoria' => $permisos->contains('301'),
            'puedeDesactivarCategoria' => $permisos->contains('305'),
        ]);
    }

    /**
     * Crea un producto del catálogo, con su categoría y una foto opcional.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->reglasProducto(), $this->mensajesProducto());

        $producto = Producto::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'],
            'idcategoria' => $validated['idcategoria'],
            'photo' => $this->guardarFoto($request),
            'estado' => true,
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'producto', $producto->id, '310');

        return redirect()->route('dashboard.productos.index')
            ->with('exito', "El producto {$producto->nombre} fue creado correctamente.");
    }

    /**
     * Modifica los datos de un producto. La foto solo cambia si se sube una
     * nueva o se marca la casilla de quitarla.
     */
    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('producto', 'nombre')->ignore($producto->id),
            ],
            'descripcion' => ['required', 'string', 'max:200'],
            'idcategoria' => [
                'required',
                'integer',
                Rule::exists('categoria', 'id')->where('estado', true),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quitar_photo' => ['nullable', 'boolean'],
        ], [
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'idcategoria.exists' => 'La categoría seleccionada ya no existe o está desactivada.',
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La foto no puede pesar más de 2 MB.',
        ]);

        $producto->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'],
            'idcategoria' => $validated['idcategoria'],
            'photo' => $this->resolverFoto($request, $producto),
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'producto', $producto->id, '311');

        return redirect()->route('dashboard.productos.index')
            ->with('exito', "El producto {$producto->nombre} fue actualizado correctamente.");
    }

    /**
     * Activa o desactiva un producto del catálogo. No borra nada: las
     * asignaciones por sucursal y sus recetas se conservan.
     */
    public function alternarEstado(Producto $producto): RedirectResponse
    {
        $nuevoEstado = ! $producto->estado;
        $producto->update(['estado' => $nuevoEstado]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'producto', $producto->id, '315');

        return redirect()->route('dashboard.productos.index')
            ->with('exito', $nuevoEstado
                ? "El producto {$producto->nombre} fue activado."
                : "El producto {$producto->nombre} fue desactivado.");
    }

    public function storeCategoria(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:categoria,nombre'],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        $categoria = Categoria::create([
            'nombre' => $validated['nombre'],
            'estado' => true,
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'categoria', $categoria->id, '300');

        return redirect()->route('dashboard.productos.index')
            ->with('exito', "La categoría {$categoria->nombre} fue creada correctamente.");
    }

    public function updateCategoria(Request $request, Categoria $categoria): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categoria', 'nombre')->ignore($categoria->id),
            ],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
        ]);

        $categoria->update(['nombre' => $validated['nombre']]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'categoria', $categoria->id, '301');

        return redirect()->route('dashboard.productos.index')
            ->with('exito', "La categoría {$categoria->nombre} fue actualizada correctamente.");
    }

    /**
     * Activa o desactiva una categoría. No se puede desactivar mientras haya
     * productos activos que la usen.
     */
    public function alternarEstadoCategoria(Categoria $categoria): RedirectResponse
    {
        if ($categoria->estado && $categoria->productos()->where('estado', true)->exists()) {
            return redirect()->route('dashboard.productos.index')
                ->with('error', "No puedes desactivar {$categoria->nombre} porque hay productos activos que la usan.");
        }

        $nuevoEstado = ! $categoria->estado;
        $categoria->update(['estado' => $nuevoEstado]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'categoria', $categoria->id, '305');

        return redirect()->route('dashboard.productos.index')
            ->with('exito', $nuevoEstado
                ? "La categoría {$categoria->nombre} fue activada."
                : "La categoría {$categoria->nombre} fue desactivada.");
    }

    /** Reglas comunes de producto para crear y para editar. */
    private function reglasProducto(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:producto,nombre'],
            'descripcion' => ['required', 'string', 'max:200'],
            'idcategoria' => [
                'required',
                'integer',
                Rule::exists('categoria', 'id')->where('estado', true),
            ],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /** Mensajes de error personalizados para las reglas comunes. */
    private function mensajesProducto(): array
    {
        return [
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'idcategoria.exists' => 'La categoría seleccionada ya no existe o está desactivada.',
            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'La foto debe ser JPG, PNG o WEBP.',
            'photo.max' => 'La foto no puede pesar más de 2 MB.',
        ];
    }

    /**
     * Decide qué foto queda guardada: una subida nueva reemplaza a la
     * anterior, "quitar foto" la borra del disco y, si no se tocó nada, se
     * conserva la que ya tenía.
     */
    private function resolverFoto(Request $request, Producto $producto): ?string
    {
        if ($request->boolean('quitar_photo')) {
            $this->borrarFoto($producto);

            return null;
        }

        $rutaNueva = $this->guardarFoto($request);

        if ($rutaNueva !== null) {
            $this->borrarFoto($producto);
        }

        return $rutaNueva ?? $producto->photo;
    }

    /** Sube la foto al disco public y devuelve su ruta relativa. */
    private function guardarFoto(Request $request): ?string
    {
        $foto = $request->file('photo');

        if (! $foto || ! $foto->isValid()) {
            return null;
        }

        return $foto->store('productos', 'public');
    }

    private function borrarFoto(Producto $producto): void
    {
        if ($producto->photo !== null) {
            Storage::disk('public')->delete($producto->photo);
        }
    }
}
