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
     * @param  array<int, array{product_id: int, presentation_id?: ?int, quantity: float, unit_cost: ?float}>  $items
     *         quantity y unit_cost en la presentación elegida (cajas y costo por caja) o en unidades si no hay
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
                $product = Product::where('company_id', $companyId)->with('presentations')->findOrFail($item['product_id']);

                $count = round((float) $item['quantity'], 3);
                if ($count <= 0) {
                    throw new BusinessException("Escribe cuántos llegaron de «{$product->name}».");
                }

                // Llegó por cajas: 3 cajas de 12 = 36 unidades; el costo escrito es por caja
                $units = 1.0;
                if (!empty($item['presentation_id'])) {
                    $presentation = $product->presentations->firstWhere('id', (int) $item['presentation_id'])
                        ?? throw new BusinessException("La presentación de «{$product->name}» ya no existe. Vuelve a elegirla.");
                    $units = (float) $presentation->units;
                }
                $quantity = round($count * $units, 3);

                // Sin costo: se asume el último costo conocido del producto
                $lineTotal = isset($item['unit_cost']) && $item['unit_cost'] !== ''
                    ? round($count * (float) $item['unit_cost'], 2)
                    : round($quantity * (float) $product->purchase_price, 2);
                $unitCost  = round($lineTotal / $quantity, 2);
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
