<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'usuario.required' => 'Ingresa tu usuario o correo.',
            'password.required' => 'Ingresa tu contraseña.',
        ];
    }
}
