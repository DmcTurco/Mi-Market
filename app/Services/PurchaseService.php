<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * "Llegó mercadería": sube el stock y actualiza el costo de cada producto.
 * Si se pagó con dinero de la caja, además registra el gasto en la caja.
 */
class PurchaseService
{
    public function __construct(private StockService $stock) {}

    /**
     * @param  array<int, array{product_id: int, quantity: float, unit_cost: ?float}>  $items
     *
     * @throws BusinessException
     */
    public function register(int $companyId, ?Employee $employee, array $items, ?string $supplier, ?CashRegister $paidFrom = null): Purchase
    {
        if (!$items) {
            throw new BusinessException('Agrega al menos un producto.');
        }

        return DB::transaction(function () use ($companyId, $employee, $items, $supplier, $paidFrom) {
            $purchase = Purchase::create([
                'company_id'  => $companyId,
                'employee_id' => $employee?->id,
                'supplier'    => $supplier ?: null,
            ]);

            $total = 0.0;
            foreach ($items as $item) {
                $product = Product::where('company_id', $companyId)->findOrFail($item['product_id']);

                $quantity = round((float) $item['quantity'], 3);
                if ($quantity <= 0) {
                    throw new BusinessException("Escribe cuántos llegaron de «{$product->name}».");
                }

                // Sin costo: se asume el último costo conocido del producto
                $unitCost  = isset($item['unit_cost']) && $item['unit_cost'] !== '' ? round((float) $item['unit_cost'], 2) : (float) $product->purchase_price;
                $lineTotal = round($quantity * $unitCost, 2);
                $total    += $lineTotal;

                $purchase->items()->create([
                    'product_id' => $product->id,
                    'quantity'   => $quantity,
                    'unit_cost'  => $unitCost,
                    'total'      => $lineTotal,
                ]);

                $this->stock->move($product->id, $quantity, StockMovement::PURCHASE, $purchase->id, $supplier ?: null);
                $product->update(['purchase_price' => $unitCost]);
            }

            $total = round($total, 2);
            $purchase->total = $total;

            if ($paidFrom) {
                if (!$paidFrom->isOpen()) {
                    throw new BusinessException('Tu caja está cerrada: no se puede pagar con dinero de la caja.');
                }

                $movement = CashMovement::create([
                    'company_id'       => $companyId,
                    'cash_register_id' => $paidFrom->id,
                    'employee_id'      => $paidFrom->employee_id,
                    'type'             => CashMovement::EXPENSE,
                    'concept'          => 'Pago de mercadería' . ($supplier ? " ({$supplier})" : ''),
                    'amount'           => $total,
                ]);
                $purchase->cash_movement_id = $movement->id;
            }

            $purchase->save();

            return $purchase;
        });
    }
}
