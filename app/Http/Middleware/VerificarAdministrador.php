<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->empleado?->rol || $user->empleado->rol->claverol !== 'ADMINISTRADOR') {
            abort(403, 'Solo el administrador cuenta con el acceso a esta sección.');
        }

        return $next($request);
    }
}
