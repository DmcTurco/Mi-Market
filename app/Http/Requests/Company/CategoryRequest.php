<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('company')->check();
    }

    /**
     * El nombre no se repite dentro de la misma empresa (otras empresas sí pueden usarlo).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories', 'name')
                    ->where('company_id', auth()->guard('company')->id())
                    ->whereNull('deleted_at')
                    ->ignore($category?->id),
            ],
            'active' => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.max'      => 'El nombre no puede tener más de 100 caracteres.',
            'name.unique'   => 'Ya tienes una categoría con ese nombre.',
        ];
    }
}
