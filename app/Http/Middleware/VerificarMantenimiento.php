<?php

namespace App\Http\Middleware;

use App\Models\ConfiguracionSistema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarMantenimiento
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(['login', 'login.store', 'logout', 'password.*'])) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->empleado?->rol?->claverol === 'ADMINISTRADOR') {
            return $next($request);
        }

        $configuracion = ConfiguracionSistema::mapa();

        if ($configuracion->get('SITIO_MANTENIMIENTO_ACTIVO') === '1') {
            return response()->view('mantenimiento', [
                'mensajeMantenimiento' => $configuracion->get('SITIO_MANTENIMIENTO_MENSAJE'),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
