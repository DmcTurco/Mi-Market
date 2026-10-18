<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Purchase;
use Illuminate\Http\Request;

/**
 * Pantalla "Llegó mercadería", compartida por la empresa y el empleado.
 */
trait HandlesPurchases
{
    use ScopedToCompany;

    /** Productos para el buscador del formulario */
    protected function purchaseProducts(): array
    {
        return $this->company()->products()
            ->where('active', true)
            ->with('presentations')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->name,
                'barcode'       => $p->barcode,
                'unit'          => $p->unitLabel(),
                'cost'          => (float) $p->purchase_price,
                'stock'         => (float) $p->stock,
                'presentations' => $p->presentations->map(fn ($pr) => [
                    'id'      => $pr->id,
                    'name'    => $pr->name,
                    'units'   => (float) $pr->units,
                    'barcode' => $pr->barcode,
                ])->values()->all(),
            ])
            ->all();
    }

    protected function validatePurchase(Request $request): array
    {
        return $request->validate([
            'supplier'             => 'nullable|string|max:150',
            'items'                => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer',
            'items.*.presentation_id' => 'nullable|integer',
            'items.*.quantity'     => 'required|numeric|min:0.001|max:9999999',
            'items.*.unit_cost'    => 'nullable|numeric|min:0|max:999999',
        ], [
            'items.required'            => 'Agrega al menos un producto.',
            'items.*.quantity.required' => 'Escribe cuántos llegaron de cada producto.',
            'items.*.quantity.min'      => 'La cantidad debe ser mayor que cero.',
        ]);
    }

    protected function showPurchase(Purchase $purchase, string $view)
    {
        $this->ensureOwned($purchase);
        $purchase->load('items.product', 'employee');

        return view($view, compact('purchase'));
    }
}
