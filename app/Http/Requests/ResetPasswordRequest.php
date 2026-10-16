<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required'],
            'correo' => ['required', 'email'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'El enlace de recuperación no es válido o ha expirado.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'password.required' => 'Debes escribir una nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'password.mixed' => 'La contraseña debe incluir al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un carácter especial (como @, #, $, etc.).',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.uncompromised' => 'Esta contraseña no es segura porque aparece en filtraciones de datos. Prueba con otra.',
            'password_confirmation.required' => 'Debes confirmar tu nueva contraseña.',
        ];
    }
}
