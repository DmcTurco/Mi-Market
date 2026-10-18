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

        // Presentaciones: se descartan las filas vacías y se limpian los números
        $presentations = collect($this->input('presentations', []))
            ->filter(fn ($p) => is_array($p) && (trim((string) ($p['name'] ?? '')) !== '' || trim((string) ($p['units'] ?? '')) !== ''))
            ->map(fn ($p) => [
                'id'      => $p['id'] ?? null,
                'name'    => trim((string) ($p['name'] ?? '')),
                'units'   => str_replace(',', '.', trim((string) ($p['units'] ?? ''))),
                'price'   => str_replace(',', '.', trim((string) ($p['price'] ?? ''))),
                'barcode' => trim((string) ($p['barcode'] ?? '')) ?: null,
            ])
            ->values()
            ->all();
        $this->merge(['presentations' => $presentations]);
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
            'barcode'         => 'nullable|string|max:30', // que no se repita lo revisa withValidator()
            'category_id'     => ['nullable', Rule::exists('categories', 'id')->where('company_id', $companyId)],
            'unit'            => ['required', Rule::in(array_keys(Product::UNITS))],
            'sale_price'      => 'required|numeric|min:0.01|max:999999',
            'purchase_price'  => 'nullable|numeric|min:0|max:999999',
            'stock'           => 'nullable|numeric|min:0|max:9999999',
            'min_stock'       => 'nullable|numeric|min:0|max:9999999',
            'igv_affectation' => ['required', Rule::in(array_keys(Product::IGV_AFFECTATIONS))],
            'active'          => 'nullable|boolean',

            'presentations'           => 'array|max:10',
            'presentations.*.id'      => 'nullable|integer',
            'presentations.*.name'    => 'required|string|max:50',
            'presentations.*.units'   => 'required|numeric|gt:1|max:99999',
            'presentations.*.price'   => 'required|numeric|min:0.01|max:999999',
            'presentations.*.barcode' => 'nullable|string|max:30',
        ];
    }

    /**
     * Un código de barras no se repite en la tienda: ni entre productos, ni entre presentaciones.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $companyId = auth()->guard('company')->id();
            $product   = $this->route('product');

            $codes = array_filter(array_merge([$this->barcode], array_column($this->input('presentations', []), 'barcode')));
            if (count($codes) !== count(array_unique($codes))) {
                $validator->errors()->add('barcode', 'Usaste el mismo código de barras dos veces en este producto.');
                return;
            }

            foreach ($codes as $code) {
                $usedByProduct = Product::where('company_id', $companyId)->where('barcode', $code)
                    ->when($product, fn ($q) => $q->where('id', '!=', $product->id))->exists();
                $usedByPresentation = \App\Models\ProductPresentation::where('company_id', $companyId)->where('barcode', $code)
                    ->when($product, fn ($q) => $q->where('product_id', '!=', $product->id))->exists();

                if ($usedByProduct || $usedByPresentation) {
                    $validator->errors()->add('barcode', "El código de barras {$code} ya lo tiene otro producto.");
                }
            }
        });
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
            'presentations.*.name.required'  => 'Escribe el nombre de cada forma de venta (ej. Caja).',
            'presentations.*.units.required' => 'Escribe cuántas unidades trae cada forma de venta.',
            'presentations.*.units.gt'       => 'Una caja o paquete debe traer más de 1 unidad.',
            'presentations.*.price.required' => 'Escribe el precio de cada forma de venta.',
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
