<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePerfilRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function index(): View
    {
        $cuenta = auth()->user();
        $empleado = $cuenta->empleado;

        return view('perfil', compact('cuenta', 'empleado'));
    }

    public function update(UpdatePerfilRequest $request): RedirectResponse
    {
        $cuenta = auth()->user();
        $empleado = $cuenta->empleado;

        $validated = $request->validated();

        if ($request->filled('password')) {
            if (! Hash::check($request->input('password_actual', ''), $cuenta->passwordhash)) {
                return back()
                    ->withInput()
                    ->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
            }
        }

        DB::statement('CALL actualizar_perfil(?, ?, ?, ?, ?, ?, ?, ?, ?)', [$empleado->id, $validated['nombre'], $validated['apellido'], $validated['telefono'],
            $validated['usuario'], $validated['correo'], $request->filled('password') ? Hash::make($validated['password']) : null, request()->userAgent(), request()->ip(), ]);

        return back()->with('exito', 'Perfil actualizado correctamente.');
    }
}
