<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'presentation_id',
        'product_name',
        'unit',
        'quantity',
        'units_per_item',
        'list_price',
        'unit_price',
        'total',
        'discount',
        'igv_affectation',
        'base',
        'igv',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'decimal:3',
            'units_per_item' => 'decimal:3',
            'list_price'     => 'decimal:2',
            'unit_price'     => 'decimal:2',
            'discount'       => 'decimal:2',
            'total'      => 'decimal:2',
            'base'       => 'decimal:2',
            'igv'        => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
