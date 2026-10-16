<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    private const CALL_REGISTRAR_ACCESO = 'CALL registrar_acceso(?, ?, ?, ?, ?, ?)';

    protected const CALL_REGISTRAR_ACCION = 'CALL registrar_accion(?, ?, ?, ?, ?, ?, ?, ?)';

    protected function registrarAccion(int $idEmpleado, string $accion, string $tabla, ?int $idRegistro, string $codigoPermiso, string $resultado = 'EXITOSO'): void
    {
        try {
            DB::statement(self::CALL_REGISTRAR_ACCION, [
                $idEmpleado,
                $accion,
                $tabla,
                $idRegistro,
                $codigoPermiso,
                $resultado,
                request()->userAgent(),
                request()->ip(),
            ]);
        } catch (\Throwable) {
        }
    }

    protected function registrarAcceso(?int $idEmpleado, string $tipo, ?string $ip, string $resultado, string $detalle): void
    {
        try {
            DB::statement(self::CALL_REGISTRAR_ACCESO, [
                $idEmpleado,
                $tipo,
                request()->userAgent(),
                $ip,
                $resultado,
                $detalle,
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * Devuelve los códigos de permiso activos del rol del usuario autenticado.
     */
    protected function codigosPermisoActuales(?Empleado $empleado = null): Collection
    {
        $empleado ??= auth()->user()->empleado;

        return $empleado->rol?->permisos
            ? $empleado->rol->permisos->where('estado', true)->pluck('codigo')
            : collect();
    }
}
