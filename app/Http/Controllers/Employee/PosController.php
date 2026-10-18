<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Concerns\ScopedToCompany;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Punto de venta. La pantalla carga todo el catálogo activo para que buscar y leer
 * el código de barras sea instantáneo; los precios y el stock se vuelven a validar al cobrar.
 */
class PosController extends Controller
{
    use ScopedToCompany;

    public function index()
    {
        $company = $this->company();

        return view('employee.pages.pos', [
            'products'   => $company->products()->where('active', true)->with('presentations')->orderBy('name')->get()->map->toPos()->all(),
            'categories' => $company->categories()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'vouchers'   => $this->availableVouchers(),
            // Reglas de la empresa para el cajero (el servidor las vuelve a validar al cobrar)
            'settings'   => [
                'allowDiscounts'     => $company->allow_discounts,
                'maxDiscountPercent' => $company->max_discount_percent,
                'rounding'           => $company->cash_rounding ? $company->rounding_favor : null,
            ],
        ]);
    }

    public function store(Request $request, SaleService $sales)
    {
        $data = $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.product_id'      => 'required|integer',
            'items.*.presentation_id' => 'nullable|integer',
            'items.*.quantity'        => 'required|numeric|min:0.001|max:99999',
            'items.*.price'           => 'nullable|numeric|min:0|max:999999',  // precio rebajado
            'discount_amount'         => 'nullable|numeric|min:0|max:999999',  // descuento a la venta en S/
            'discount_percent'        => 'nullable|numeric|min:0|max:100',     // o en %
            'voucher_type'        => ['required', Rule::in($this->availableVouchers())],
            'payment_method'      => ['required', Rule::in(array_keys(Sale::PAYMENT_METHODS))],
            'amount_received'     => 'nullable|numeric|min:0|max:999999',
            'client_id'           => 'nullable|integer',
            'customer_name'       => 'nullable|string|max:150',
            'customer_document'   => 'nullable|string|max:15',
            'customer_address'    => 'nullable|string|max:200',
            'customer_phone'      => 'nullable|string|max:20',
        ], [
            'items.required'        => 'Agrega al menos un producto a la venta.',
            'voucher_type.in'       => 'Ese comprobante no está disponible.',
        ]);

        $sale = $sales->create(auth()->user(), $request->attributes->get('cash_register'), $data);

        return response()->json([
            'id'         => $sale->id,
            'number'     => $sale->voucherNumber(),
            'total'      => (float) $sale->total,
            'charged'    => $sale->charged(),
            'change'     => $sale->change !== null ? (float) $sale->change : null,
            'ticket_url' => route('employee.sales.ticket', $sale),
            // Stock actualizado de lo vendido, para que la pantalla no muestre datos viejos
            'stock'      => $sale->items()->with('product')->get()
                ->mapWithKeys(fn ($item) => [$item->product_id => (float) $item->product->stock]),
        ]);
    }

    /**
     * Boleta y factura solo cuando la empresa tiene la facturación electrónica configurada.
     *
     * @return string[]
     */
    private function availableVouchers(): array
    {
        return $this->company()->sunatEnabled()
            ? [Sale::TICKET, Sale::BOLETA, Sale::FACTURA]
            : [Sale::TICKET];
    }
}
