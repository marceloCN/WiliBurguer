<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cuentaId = $this->user()?->id;

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'usuario' => ['required', 'string', 'max:255', "unique:cuentausuario,usuario,{$cuentaId}"],
            'correo' => ['required', 'email', 'max:255', "unique:cuentausuario,correo,{$cuentaId}"],
            'password_actual' => ['required_with:password', 'string'],
            'password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed'],
            'password_confirmation' => ['required_with:password', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required_with' => 'Para cambiar tu contraseña, escribe primero tu contraseña actual.',
            'password.min' => 'La nueva contraseña debe tener al menos :min caracteres.',
            'password.mixed' => 'La nueva contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La nueva contraseña debe incluir al menos un número.',
            'password.symbols' => 'La nueva contraseña debe incluir al menos un carácter especial (como @, #, $, etc.).',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.uncompromised' => 'Esta contraseña no es segura porque aparece en filtraciones de datos. Prueba con otra.',
            'password_confirmation.required_with' => 'Debes confirmar tu nueva contraseña escribiéndola de nuevo.',
        ];
    }
}
