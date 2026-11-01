<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PermisoController extends Controller
{
    /**
     * Muestra la interfaz para gestionar los permisos de un rol.
     * Solo los administradores pueden acceder a esta sección.
     */
    public function index(Request $request): View
    {
        $roles = Rol::withCount(['permisos' => fn ($query) => $query->where('estado', true)])
            ->orderBy('id')
            ->get();

        $rolSeleccionado = $this->rolDesdeRequest($request, $roles);

        $permisosPorCategoria = Permiso::where('estado', true)
            ->orderBy('codigo')
            ->get()
            ->groupBy(fn (Permiso $permiso) => $this->categoriaDelPermiso($permiso));

        $permisosAsignados = $rolSeleccionado
            ? $rolSeleccionado
                ->permisos()
                ->where('estado', true)
                ->pluck('permiso.id')
                ->toArray()
            : [];

        return view('admin.permisos.index', compact(
            'roles',
            'rolSeleccionado',
            'permisosPorCategoria',
            'permisosAsignados'
        ));
    }

    /**
     * Sincroniza los permisos seleccionados para el rol indicado.
     */
    public function actualizar(Request $request, Rol $rol): RedirectResponse
    {
        $permisos = $request->input('permisos', []);
        $permisosValidos = array_map('intval', array_intersect(
            $permisos,
            Permiso::where('estado', true)->pluck('id')->toArray()
        ));

        $rol->permisos()->sync($permisosValidos);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'rolpermiso', $rol->id, '50');

        return redirect()
            ->route('dashboard.permisos.index', ['rol' => $rol->id])
            ->with('exito', "Permisos de {$rol->nombrerol} actualizados correctamente.");
    }

    /**
     * Resuelve el rol a mostrar a partir del query string o usa el primero.
     *
     * @param  Collection<int, Rol>  $roles
     */
    private function rolDesdeRequest(Request $request, Collection $roles): ?Rol
    {
        if ($request->has('rol')) {
            $rol = $roles->firstWhere('id', (int) $request->input('rol'));

            if ($rol) {
                return $rol;
            }
        }

        return $roles->first();
    }

    /**
     * Devuelve una categoría legible según el rango del código del permiso.
     */
    private function categoriaDelPermiso(Permiso $permiso): string
    {
        $codigo = (int) $permiso->codigo;

        return match (true) {
            $codigo < 100 => 'Configuración y sistema',
            $codigo >= 100 && $codigo < 150 => 'Empleados y usuarios',
            $codigo >= 150 && $codigo < 200 => 'Bitácoras',
            $codigo >= 200 && $codigo < 300 => 'Sucursales, horarios, mesas y cajas',
            $codigo >= 300 && $codigo < 400 => 'Productos e inventario',
            $codigo >= 400 && $codigo < 500 => 'Pedidos y ventas',
            $codigo >= 500 && $codigo < 600 => 'Promociones y marketing',
            default => 'Otros permisos',
        };
    }
}
