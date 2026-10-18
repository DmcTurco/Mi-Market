<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de stock de un producto (kardex simple).
 */
class StockMovement extends Model
{
    const SALE       = 'sale';
    const SALE_VOID  = 'sale_void';
    const PURCHASE      = 'purchase';
    const PURCHASE_VOID = 'purchase_void';
    const ADJUSTMENT    = 'adjustment';

    const REASONS = [
        self::SALE          => 'Venta',
        self::SALE_VOID     => 'Venta anulada',
        self::PURCHASE      => 'Llegó mercadería',
        self::PURCHASE_VOID => 'Mercadería anulada',
        self::ADJUSTMENT    => 'Corrección de stock',
    ];

    protected $fillable = [
        'company_id',
        'product_id',
        'reason',
        'reference_id',
        'quantity',
        'balance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'balance'  => 'decimal:3',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
