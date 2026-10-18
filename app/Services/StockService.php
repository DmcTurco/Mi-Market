<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Qty;
use App\Exceptions\BusinessException;

/**
 * Sube o baja el stock de un producto y deja el rastro en stock_movements.
 * Debe llamarse DENTRO de una transacción: bloquea la fila del producto (FOR UPDATE).
 */
class StockService
{
    /**
     * @param  float  $delta  positivo = entra, negativo = sale
     * @return float  stock que quedó
     *
     * @throws BusinessException si una salida deja el stock en negativo
     */
    public function move(int $productId, float $delta, string $reason, ?int $referenceId = null, ?string $notes = null): float
    {
        $product = Product::withTrashed()->lockForUpdate()->findOrFail($productId);
        $balance = round((float) $product->stock + $delta, 3);

        if ($delta < 0 && $balance < 0) {
            throw new BusinessException(
                "No alcanza el stock de «{$product->name}»: quedan " . Qty::format($product->stock)
                . ' y se necesitan ' . Qty::format(abs($delta)) . '. Si llegó mercadería, regístrala primero.'
            );
        }

        $product->update(['stock' => $balance]);

        StockMovement::create([
            'company_id'   => $product->company_id,
            'product_id'   => $product->id,
            'reason'       => $reason,
            'reference_id' => $referenceId,
            'quantity'     => $delta,
            'balance'      => $balance,
            'notes'        => $notes,
        ]);

        return $balance;
    }
}
