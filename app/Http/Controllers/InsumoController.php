<?php

namespace App\Http\Controllers;

use App\Models\Insumo;
use App\Models\UnidadMedida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InsumoController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = $request->string('busqueda')->trim()->toString();

        $insumos = Insumo::with('unidadMedida')
            ->when($busqueda, fn ($query) => $query->where('nombre', 'ilike', "%{$busqueda}%"))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        $unidades = UnidadMedida::orderBy('nombre')->get();

        $tiposUnidad = [
            'PESO' => 'Peso (g, kg, lb, oz)',
            'VOLUMEN' => 'Volumen (ml, l, gal)',
            'UNIDAD' => 'Unidad / pieza (und, pza, docena)',
            'LONGITUD' => 'Longitud (cm, m)',
            'OTRO' => 'Otro (cualquier abreviatura personalizada)',
        ];

        $unidadesEnUso = Insumo::pluck('idunidadmedida')->unique()->all();

        $empleado = auth()->user()->empleado->load('rol.permisos');
        $permisos = $this->codigosPermisoActuales($empleado);

        return view('admin.insumos.index', compact(
            'insumos',
            'unidades',
            'tiposUnidad',
            'unidadesEnUso',
            'busqueda',
        ))->with([
            'puedeCrearUnidad' => $permisos->contains('330'),
            'puedeEditarUnidad' => $permisos->contains('331'),
            'puedeDesactivarUnidad' => $permisos->contains('335'),
            'puedeCrearInsumo' => $permisos->contains('336'),
            'puedeEditarInsumo' => $permisos->contains('337'),
            'puedeDesactivarInsumo' => $permisos->contains('338'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:insumo,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'idunidadmedida' => ['required', 'exists:unidadmedida,id'],
        ]);

        $insumo = Insumo::create([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'idunidadmedida' => $validated['idunidadmedida'],
            'estado' => true,
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'insumo', $insumo->id, '336');

        return redirect()->route('dashboard.insumos.index')
            ->with('exito', "El insumo {$validated['nombre']} fue creado correctamente.");
    }

    public function update(Request $request, Insumo $insumo): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:insumo,nombre,'.$insumo->id],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'idunidadmedida' => ['required', 'exists:unidadmedida,id'],
        ]);

        $insumo->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'idunidadmedida' => $validated['idunidadmedida'],
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'insumo', $insumo->id, '337');

        return redirect()->route('dashboard.insumos.index')
            ->with('exito', "El insumo {$validated['nombre']} fue actualizado correctamente.");
    }

    public function alternarEstado(Insumo $insumo): RedirectResponse
    {
        $nuevoEstado = ! $insumo->estado;
        $insumo->update(['estado' => $nuevoEstado]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'insumo', $insumo->id, '338');

        $mensaje = $nuevoEstado
            ? "El insumo {$insumo->nombre} fue activado."
            : "El insumo {$insumo->nombre} fue desactivado.";

        return redirect()->route('dashboard.insumos.index')->with('exito', $mensaje);
    }

    public function storeUnidad(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'unique:unidadmedida,nombre'],
            'abreviatura' => ['required', 'string', 'max:10', 'unique:unidadmedida,abreviatura'],
            'tipo' => ['required', 'string', 'max:20'],
        ]);

        $unidad = UnidadMedida::create([
            'nombre' => $validated['nombre'],
            'abreviatura' => mb_strtolower($validated['abreviatura']),
            'tipo' => $validated['tipo'],
            'estado' => true,
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'CREATE', 'unidadmedida', $unidad->id, '330');

        return redirect()->route('dashboard.insumos.index')
            ->with('exito', "La unidad {$validated['nombre']} ({$validated['abreviatura']}) fue creada correctamente.");
    }

    public function updateUnidad(Request $request, UnidadMedida $unidad): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'unique:unidadmedida,nombre,'.$unidad->id],
            'abreviatura' => ['required', 'string', 'max:10', 'unique:unidadmedida,abreviatura,'.$unidad->id],
            'tipo' => ['required', 'string', 'max:20'],
        ]);

        $unidad->update([
            'nombre' => $validated['nombre'],
            'abreviatura' => mb_strtolower($validated['abreviatura']),
            'tipo' => $validated['tipo'],
        ]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'unidadmedida', $unidad->id, '331');

        return redirect()->route('dashboard.insumos.index')
            ->with('exito', "La unidad {$validated['nombre']} fue actualizada correctamente.");
    }

    public function alternarEstadoUnidad(UnidadMedida $unidad): RedirectResponse
    {
        if ($unidad->estado && $unidad->insumos()->where('estado', true)->exists()) {
            return redirect()->route('dashboard.insumos.index')
                ->with('error', "No puedes desactivar {$unidad->nombre} porque hay insumos activos que la usan.");
        }

        $nuevoEstado = ! $unidad->estado;
        $unidad->update(['estado' => $nuevoEstado]);

        $this->registrarAccion(auth()->user()->empleado->id, 'UPDATE', 'unidadmedida', $unidad->id, '335');

        $mensaje = $nuevoEstado
            ? "La unidad {$unidad->nombre} fue activada."
            : "La unidad {$unidad->nombre} fue desactivada.";

        return redirect()->route('dashboard.insumos.index')->with('exito', $mensaje);
    }

    public function ayudaPdf(Request $request): Response
    {
        $empleado = auth()->user()->empleado->load('rol');

        $unidades = UnidadMedida::where('estado', true)
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();
        $insumos = Insumo::with('unidadMedida')
            ->where('estado', true)
            ->orderBy('idunidadmedida')
            ->orderBy('nombre')
            ->get();
        $generado = now();

        $pdf = Pdf::loadView(
            'admin.insumos.pdf.ayuda',
            compact('empleado', 'unidades', 'insumos', 'generado')
        )->setPaper('a4', 'portrait');

        return $pdf->download('guia-insumos-y-unidades-'.$generado->format('Ymd').'.pdf');
    }
}
