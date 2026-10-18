<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'presentation_id',
        'entered_quantity',
        'entered_cost',
        'quantity',
        'unit_cost',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'entered_quantity' => 'decimal:3',
            'entered_cost'     => 'decimal:2',
            'quantity'  => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'total'     => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
