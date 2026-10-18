<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('company')->check();
    }

    /**
     * Store exige contraseña; en update es opcional (vacía = no cambia).
     * El correo es único en todo el sistema porque es el usuario del login /employee.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'name'     => 'required|string|max:150',
            'email'    => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->whereNull('deleted_at')->ignore($employee?->id)],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'active'   => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre del empleado es obligatorio.',
            'email.required'     => 'El correo es obligatorio (es su usuario para entrar).',
            'email.email'        => 'Ingresa un correo válido.',
            'email.unique'       => 'Ese correo ya lo usa otro empleado.',
            'password.required'  => 'La contraseña es obligatoria.',
            'password.min'       => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
