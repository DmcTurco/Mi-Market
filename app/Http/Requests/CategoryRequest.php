<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        // Verifica si estás editando un registro existente
        if ($this->isMethod('PUT')) {
            $rules['name'][] = 'unique:categories,name,' . $this->category->id;
        } else {
            $rules['name'][] = 'unique:categories,name';
        }

        return $rules;
    }


    public function withValidator($validator)
    {

        $validator->after(function ($validator) {
            if ($validator->errors()->count()) {
                if (!in_array($this->method(), ['PUT', 'PATCH'])) {
                    $validator->errors()->add('post', true);
                }
            }
        });
    }
}
