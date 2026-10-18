<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Support\Qty;
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

            $lines = $this->buildLines($companyId, $items);

            foreach ($lines as $line) {
                $purchase->items()->create($line['row']);
                $this->stock->move($line['product']->id, $line['row']['quantity'], StockMovement::PURCHASE, $purchase->id, $supplier ?: null);
                $line['product']->update(['purchase_price' => $line['row']['unit_cost']]);
            }

            $total = round(array_sum(array_map(fn ($l) => $l['row']['total'], $lines)), 2);
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

            AuditLog::record('purchase.created',
                'Registró mercadería' . ($supplier ? " de {$supplier}" : '') . ' por ' . Qty::money($total) . ' (' . count($lines) . ' productos)'
                    . ($paidFrom ? ', pagada con dinero de la caja' : ''),
                $purchase, ['total' => $total, 'productos' => $this->auditItems($lines)]);

            return $purchase;
        });
    }

    /**
     * Corrige un ingreso (cantidades, costos, productos, proveedor). El stock se ajusta solo por la
     * diferencia de cada producto (eran 24 y ahora 12 → baja 12) y queda en el historial como
     * "Mercadería corregida". Si se pagó con dinero de la caja, el gasto pasa al nuevo total.
     *
     * @throws BusinessException si se quita mercadería que ya se vendió, o si cambia el total de una caja ya cerrada
     */
    public function update(Purchase $purchase, array $items, ?string $supplier): Purchase
    {
        if (!$items) {
            throw new BusinessException('Deja al menos un producto. Si la mercadería no llegó, anula el ingreso.');
        }

        return DB::transaction(function () use ($purchase, $items, $supplier) {
            $purchase = Purchase::lockForUpdate()->with('items.product', 'cashMovement.cashRegister')->findOrFail($purchase->id);

            if ($purchase->isVoided()) {
                throw new BusinessException('Este ingreso está anulado: ya no se puede editar.');
            }

            $lines = $this->buildLines($purchase->company_id, $items);
            $total = round(array_sum(array_map(fn ($l) => $l['row']['total'], $lines)), 2);

            // Pagado con una caja que ya se cerró: su cuadre está hecho, el monto no puede cambiar
            $movement = $purchase->cashMovement;
            if ($movement && !$movement->cashRegister->isOpen() && abs($total - (float) $purchase->total) >= 0.005) {
                throw new BusinessException('Se pagó con una caja que ya se cerró: el total debe seguir siendo '
                    . Qty::money($purchase->total) . '. Puedes corregir cantidades y productos, pero no el monto.');
            }

            // Diferencia de stock por producto: lo nuevo − lo que ya había entrado con este ingreso
            $before = [];
            foreach ($purchase->items as $item) {
                $before[$item->product_id] = ($before[$item->product_id] ?? 0) + (float) $item->quantity;
            }
            $after = [];
            foreach ($lines as $line) {
                $after[$line['product']->id] = ($after[$line['product']->id] ?? 0) + $line['row']['quantity'];
            }

            foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $productId) {
                $delta = round(($after[$productId] ?? 0) - ($before[$productId] ?? 0), 3);
                if ($delta == 0) {
                    continue;
                }

                try {
                    $this->stock->move($productId, $delta, StockMovement::PURCHASE_EDIT, $purchase->id, 'Corrección del ingreso');
                } catch (BusinessException) {
                    $product = Product::withTrashed()->find($productId);
                    throw new BusinessException(
                        "No se puede quitar tanto de «{$product->name}»: ya se vendió parte (quedan " . Qty::format($product->stock)
                        . ' y habría que quitar ' . Qty::format(abs($delta)) . ').'
                    );
                }
            }

            $purchase->items()->delete();
            foreach ($lines as $line) {
                $purchase->items()->create($line['row']);
                $line['product']->update(['purchase_price' => $line['row']['unit_cost']]);
            }

            if ($movement && $movement->cashRegister->isOpen()) {
                $movement->update([
                    'amount'  => $total,
                    'concept' => 'Pago de mercadería' . ($supplier ? " ({$supplier})" : ''),
                ]);
            }

            $oldTotal = (float) $purchase->total;
            $purchase->update(['supplier' => $supplier ?: null, 'total' => $total]);

            AuditLog::record('purchase.updated',
                "Corrigió el ingreso de mercadería #{$purchase->id}: total " . Qty::money($oldTotal) . ' → ' . Qty::money($total),
                $purchase, ['antes' => ['total' => $oldTotal, 'productos' => $before], 'después' => ['total' => $total, 'productos' => $this->auditItems($lines)]]);

            return $purchase;
        });
    }

    /** Resumen de las líneas para la bitácora: ["Cerveza" => "2 × 60.00"] */
    private function auditItems(array $lines): array
    {
        return collect($lines)->mapWithKeys(fn ($l) => [
            $l['product']->name => Qty::format($l['row']['entered_quantity']) . ' × ' . number_format($l['row']['entered_cost'], 2),
        ])->all();
    }

    /**
     * Convierte lo que escribió el usuario en líneas listas para guardar:
     * "2 cajas a S/ 60" → 24 unidades a S/ 5.00 (total S/ 120). Sin costo, se usa el último conocido.
     *
     * @return array<int, array{product: Product, row: array<string, mixed>}>
     *
     * @throws BusinessException
     */
    private function buildLines(int $companyId, array $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            $product = Product::where('company_id', $companyId)->with('presentations')->findOrFail($item['product_id']);

            $count = round((float) $item['quantity'], 3);
            if ($count <= 0) {
                throw new BusinessException("Escribe cuántos llegaron de «{$product->name}».");
            }

            $presentation = null;
            if (!empty($item['presentation_id'])) {
                $presentation = $product->presentations->firstWhere('id', (int) $item['presentation_id'])
                    ?? throw new BusinessException("La presentación de «{$product->name}» ya no existe. Vuelve a elegirla.");
            }
            $units    = $presentation ? (float) $presentation->units : 1.0;
            $quantity = round($count * $units, 3);

            $enteredCost = isset($item['unit_cost']) && $item['unit_cost'] !== ''
                ? round((float) $item['unit_cost'], 2)
                : round((float) $product->purchase_price * $units, 2);
            $lineTotal = round($count * $enteredCost, 2);

            $lines[] = [
                'product' => $product,
                'row'     => [
                    'product_id'       => $product->id,
                    'presentation_id'  => $presentation?->id,
                    'entered_quantity' => $count,
                    'entered_cost'     => $enteredCost,
                    'quantity'         => $quantity,
                    'unit_cost'        => round($lineTotal / $quantity, 2),
                    'total'            => $lineTotal,
                ],
            ];
        }

        return $lines;
    }

    /**
     * Anula un ingreso registrado por error: el stock vuelve a como estaba, el costo del producto
     * vuelve al de su ingreso anterior y, si se pagó con dinero de una caja que sigue abierta,
     * el gasto se borra (el dinero vuelve a esa caja).
     *
     * @throws BusinessException si ya se vendió parte de esa mercadería y no alcanza el stock para devolverla
     */
    public function void(Purchase $purchase, string $reason): void
    {
        DB::transaction(function () use ($purchase, $reason) {
            $purchase = Purchase::lockForUpdate()->with('items.product', 'cashMovement.cashRegister')->findOrFail($purchase->id);

            if ($purchase->isVoided()) {
                throw new BusinessException('Este ingreso ya estaba anulado.');
            }

            foreach ($purchase->items as $item) {
                try {
                    $this->stock->move($item->product_id, -(float) $item->quantity, StockMovement::PURCHASE_VOID, $purchase->id, $reason);
                } catch (BusinessException) {
                    throw new BusinessException(
                        "No se puede anular: ya se vendió parte de «{$item->product->name}» (quedan " . Qty::format($item->product->fresh()->stock)
                        . ' y habría que quitar ' . Qty::format($item->quantity) . '). Corrige el stock desde Productos → Editar.'
                    );
                }

                // El costo vuelve al del último ingreso válido de ese producto (si hay)
                $previousCost = PurchaseItem::where('product_id', $item->product_id)
                    ->where('purchase_id', '!=', $purchase->id)
                    ->whereHas('purchase', fn ($q) => $q->where('status', Purchase::ACTIVE))
                    ->latest('id')
                    ->value('unit_cost');
                if ($previousCost !== null) {
                    $item->product->update(['purchase_price' => $previousCost]);
                }
            }

            // Pagado con la caja: si sigue abierta, el dinero vuelve; si ya se cerró, el cuadre no se toca
            $movement = $purchase->cashMovement;
            if ($movement && $movement->cashRegister->isOpen()) {
                $purchase->cash_movement_id = null;
                $movement->delete();
            }

            $purchase->update([
                'status'      => Purchase::VOIDED,
                'voided_at'   => now(),
                'void_reason' => $reason,
            ]);

            AuditLog::record('purchase.void',
                "Anuló el ingreso de mercadería #{$purchase->id} de " . Qty::money($purchase->total) . ": {$reason}",
                $purchase, ['total' => (float) $purchase->total, 'motivo' => $reason]);
        });
    }
}
