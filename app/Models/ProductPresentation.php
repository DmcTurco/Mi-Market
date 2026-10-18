<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Otra forma de vender un producto (caja, media caja...). units = cuántas unidades base trae.
 */
class ProductPresentation extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'name',
        'units',
        'price',
        'barcode',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'decimal:3',
            'price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** "Caja x12" */
    public function label(): string
    {
        return $this->name . ' x' . \App\Support\Qty::format($this->units);
    }
}
