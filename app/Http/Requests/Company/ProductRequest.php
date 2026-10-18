<?php

namespace App\Http\Requests\Company;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->guard('company')->check();
    }

    protected function prepareForValidation(): void
    {
        // Precios con coma decimal ("3,50") también valen
        foreach (['sale_price', 'purchase_price', 'stock', 'min_stock'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => str_replace(',', '.', trim($this->input($field)))]);
            }
        }
        $this->merge(['barcode' => trim((string) $this->barcode) ?: null]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->guard('company')->id();
        $product   = $this->route('product');

        return [
            'name'            => 'required|string|max:150',
            'barcode'         => [
                'nullable', 'string', 'max:30',
                Rule::unique('products', 'barcode')->where('company_id', $companyId)->whereNull('deleted_at')->ignore($product?->id),
            ],
            'category_id'     => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'unit'            => ['required', Rule::in(array_keys(Product::UNITS))],
            'sale_price'      => 'required|numeric|min:0.01|max:999999',
            'purchase_price'  => 'nullable|numeric|min:0|max:999999',
            'stock'           => 'nullable|numeric|min:0|max:9999999',
            'min_stock'       => 'nullable|numeric|min:0|max:9999999',
            'igv_affectation' => ['required', Rule::in(array_keys(Product::IGV_AFFECTATIONS))],
            'active'          => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'       => 'Escribe el nombre del producto.',
            'barcode.unique'      => 'Ese código de barras ya lo tiene otro producto.',
            'sale_price.required' => 'Escribe el precio de venta.',
            'sale_price.min'      => 'El precio de venta debe ser mayor que cero.',
            'sale_price.numeric'  => 'El precio debe ser un número, por ejemplo 3.50',
            'stock.numeric'       => 'El stock debe ser un número.',
        ];
    }

    /** Datos del producto (sin el stock, que se mueve con StockService) */
    public function productData(): array
    {
        return [
            'name'            => $this->name,
            'barcode'         => $this->barcode,
            'category_id'     => $this->category_id ?: null,
            'unit'            => $this->unit,
            'sale_price'      => $this->sale_price,
            'purchase_price'  => $this->purchase_price ?: 0,
            'min_stock'       => $this->min_stock ?: 0,
            'igv_affectation' => $this->igv_affectation,
            'active'          => $this->boolean('active', true),
        ];
    }
}
