<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\CuentaUsuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request): JsonResponse
    {
        $cuenta = CuentaUsuario::with('empleado')
            ->where('usuario', $request->usuario)
            ->orWhere('correo', $request->usuario)
            ->first();

        $errores = $this->validarCredenciales($request, $cuenta);

        if (! empty($errores)) {
            return $this->respuestaError($errores);
        }

        Auth::login($cuenta, $request->boolean('remember'));

        $cuenta->update([
            'ultimoacceso' => now(),
            'intentosfallidos' => 0,
            'bloqueado' => false,
            'bloqueadohasta' => null,
        ]);

        $this->registrarAcceso($cuenta->idempleado, 'LOGIN', $request->ip(), 'EXITOSO', 'Acceso correcto');

        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect' => route('dashboard'),
        ]);
    }

    private function validarCredenciales(LoginRequest $request, ?CuentaUsuario $cuenta): array
    {
        $ip = $request->ip();

        if (! $cuenta) {
            $this->registrarAcceso(null, 'LOGIN', $ip, 'FALLIDO', 'Cuenta no encontrada');

            return ['usuario' => 'Tu cuenta de usuario es incorrecta.'];
        }

        if (! $cuenta->empleado?->estado) {
            $this->registrarAcceso($cuenta->idempleado, 'LOGIN', $ip, 'FALLIDO', 'Cuenta de empleado bloqueado intentando ingresar');

            return ['usuario' => 'Cuenta de empleado bloqueada.'];
        }

        if ($cuenta->bloqueado) {
            $verificar_cuenta = DB::selectOne('SELECT verificar_bloqueo_y_desbloquear(?) AS desbloqueado', [$cuenta->idempleado]);
            if (! $verificar_cuenta?->desbloqueado) {
                $this->registrarAcceso($cuenta->idempleado, 'LOGIN', $ip, 'FALLIDO', 'Cuenta bloqueada');

                return ['usuario' => 'Cuenta bloqueada temporalmente.'];
            }
        }

        if (! Hash::check($request->password, $cuenta->passwordhash)) {
            $nuevosIntentos = ($cuenta->intentosfallidos ?? 0) + 1;
            $update = ['intentosfallidos' => $nuevosIntentos];
            if ($nuevosIntentos >= 3) {
                $update['bloqueado'] = true;
                $update['bloqueadohasta'] = now()->addMinutes(15);
            }
            $cuenta->update($update);
            $this->registrarAcceso($cuenta->idempleado, 'LOGIN', $ip, 'FALLIDO', 'Contraseña incorrecta');

            return ['password' => 'Credenciales incorrectas. Intentos restantes: '.max(0, 3 - $nuevosIntentos)];
        }

        if ($cuenta->bloqueado && $cuenta->bloqueadohasta?->isFuture()) {
            $this->registrarAcceso($cuenta->idempleado, 'LOGIN', $ip, 'FALLIDO', 'Cuenta bloqueada');

            return ['usuario' => 'Cuenta bloqueada temporalmente.'];
        }

        return [];
    }

    private function respuestaError(array $errores): JsonResponse
    {
        $erroresFormateados = [];
        foreach ($errores as $campo => $mensaje) {
            $erroresFormateados[$campo] = (array) $mensaje;
        }

        return response()->json([
            'success' => false,
            'message' => 'Los datos proporcionados no son válidos.',
            'errors' => $erroresFormateados,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $cuenta = Auth::user();

        if ($cuenta && $cuenta->empleado) {
            $this->registrarAcceso($cuenta->idempleado, 'LOGOUT', $request->ip(), 'EXITOSO', 'Sesión cerrada');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }
}
