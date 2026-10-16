<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Models\CuentaUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'correo' => ['required', 'email'],
        ]);

        $cuenta = CuentaUsuario::with('empleado')->where('correo', $request->correo)->first();

        if (! $cuenta) {
            return back()->withErrors(['correo' => 'No encontramos una cuenta con ese correo.']);
        }

        // Un empleado dado de baja no puede restablecer la contraseña, aunque
        // su cuenta esté activa; en cambio, una cuenta bloqueada sí puede hacerlo.
        if (! $cuenta->empleado || $cuenta->empleado->estado !== true) {
            return back()->withErrors(['correo' => 'La cuenta con ese correo pertenece a un empleado dado de baja y no puede restablecer la contraseña.']);
        }

        $status = Password::broker()->sendResetLink(
            ['correo' => $request->correo]
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'Te enviamos el enlace de recuperación a tu correo.')
            : back()->withErrors(['correo' => 'No encontramos una cuenta con ese correo.']);
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'correo' => $request->query('correo', ''),
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $cuenta = CuentaUsuario::with('empleado')->where('correo', $request->correo)->first();

        if (! $cuenta || ! $cuenta->empleado || $cuenta->empleado->estado !== true) {
            return back()->withErrors(['correo' => 'No pudimos restablecer tu contraseña. Revisa los datos.']);
        }

        $status = Password::broker()->reset(
            $request->only('correo', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'passwordhash' => Hash::make($password),
                    'intentosfallidos' => 0,
                    'bloqueado' => false,
                    'bloqueadohasta' => null,
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('inicio')->with('exito', 'Tu contraseña fue modificada correctamente. Inicia sesión con tu nueva contraseña.')
            : back()->withErrors(['correo' => 'No pudimos restablecer tu contraseña. Revisa los datos.']);
    }
}
