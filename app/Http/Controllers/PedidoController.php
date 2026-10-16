<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PedidoController extends Controller
{
    /**
     * Ventana de atención (POS): sucursal, estado de la apertura de caja,
     * categorías con productos, promociones, mesas y pedidos del día.
     */
    public function index(Request $request): View
    {
        $empleado = auth()->user()->empleado->load('rol');
        $rol = $empleado->rol;

        $sucursales = $this->sucursalesPermitidas($empleado);

        $sucursalActual = $sucursales->firstWhere('id', $request->integer('idsucursal'))
            ?? $sucursales->first();

        abort_unless($sucursalActual, 403, 'No tienes una sucursal asignada para atender pedidos.');

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
