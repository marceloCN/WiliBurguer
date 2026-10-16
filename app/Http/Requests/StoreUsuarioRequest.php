<?php

namespace App\Http\Requests;

use App\Models\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $esGerente = auth()->user()?->empleado?->rol?->claverol === 'GERENTE';

        $rolesPermitidos = $esGerente
            ? Rol::whereIn('claverol', ['CAJERO', 'MESERO'])->pluck('id')->all()
            : Rol::pluck('id')->all();

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'idrol' => ['required', 'exists:rol,id', Rule::in($rolesPermitidos)],
            'usuario' => ['required', 'string', 'max:255', 'unique:cuentausuario,usuario'],
            'correo' => ['required', 'email', 'max:255', 'unique:cuentausuario,correo'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del empleado es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            'apellido.required' => 'El apellido del empleado es obligatorio.',
            'apellido.max' => 'El apellido no puede tener más de 100 caracteres.',
            'telefono.max' => 'El teléfono no puede tener más de 15 caracteres.',
            'idrol.required' => 'Debes seleccionar un rol para el empleado.',
            'idrol.exists' => 'El rol seleccionado no existe.',
            'idrol.in' => 'No tienes permiso para asignar ese rol.',
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.max' => 'El usuario no puede tener más de 255 caracteres.',
            'usuario.unique' => 'Ese nombre de usuario ya está registrado.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no tiene un formato válido.',
            'correo.max' => 'El correo no puede tener más de 255 caracteres.',
            'correo.unique' => 'Ese correo ya está registrado.',
        ];
    }
}
