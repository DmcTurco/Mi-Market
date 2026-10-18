<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    /**
     * Solo el administrador del sistema gestiona empresas (la ruta ya exige auth:admin).
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    /**
     * Store exige contraseña; en update es opcional (vacía = no cambia).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name'     => 'required|string|max:150',
            'ruc'      => ['nullable', 'digits:11', Rule::unique('companies', 'ruc')->ignore($company?->id)],
            'address'  => 'nullable|string|max:200',
            'phone'    => 'nullable|string|max:30',
            'email'    => ['required', 'email', 'max:255', Rule::unique('companies', 'email')->ignore($company?->id)],
            'password' => [$company ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre de la tienda es obligatorio.',
            'ruc.digits'         => 'El RUC debe tener 11 dígitos.',
            'ruc.unique'         => 'Ese RUC ya está registrado en otra empresa.',
            'email.required'     => 'El correo es obligatorio (es el usuario para entrar al panel).',
            'email.email'        => 'Ingresa un correo válido.',
            'email.unique'       => 'Ese correo ya lo usa otra empresa.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
