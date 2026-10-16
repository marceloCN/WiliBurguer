<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $user = $request->user();

        if (! $user?->empleado?->rol) {
            abort(403, 'No tienes un rol asignado en el sistema. Contacta al administrador para que te asigne un rol.');
        }

        $usuarioPermisos = $user->empleado->rol->permisos
            ->where('estado', true)
            ->pluck('codigo');

        $tieneTodos = collect($permisos)->diff($usuarioPermisos)->isEmpty();

        if (! $tieneTodos) {
            $requeridos = implode(', ', $permisos);
            abort(403, "No tienes permiso para acceder a esta sección. Permisos requeridos: {$requeridos}. Tu rol actual no tiene todos estos permisos. Solicítalo al administrador del sistema.");
        }

        return $next($request);
    }
}
