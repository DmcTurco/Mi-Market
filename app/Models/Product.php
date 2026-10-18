<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Producto de la tienda. El precio de venta es el precio FINAL que paga el cliente (IGV incluido):
 * al emitir boleta o factura el sistema separa la base y el IGV (ver App\Services\TaxCalculator).
 */
class Product extends Model
{
    use HasFactory, SoftDeletes;

    /** Unidades de venta: [nombre para el usuario, código SUNAT, ¿se vende en fracciones?] */
    const UNITS = [
        'unidad'  => ['Unidad', 'NIU', false],
        'kg'      => ['Kilo', 'KGM', true],
        'litro'   => ['Litro', 'LTR', true],
        'paquete' => ['Paquete', 'PK', false],
        'caja'    => ['Caja', 'BX', false],
    ];

    const IGV_AFFECTATIONS = [
        '10' => 'Con IGV',
        '20' => 'Exonerado',
        '30' => 'Inafecto',
    ];

    protected $fillable = [
        'company_id',
        'category_id',
        'barcode',
        'name',
        'unit',
        'purchase_price',
        'sale_price',
        'stock',
        'min_stock',
        'igv_affectation',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price'     => 'decimal:2',
            'stock'          => 'decimal:3',
            'min_stock'      => 'decimal:3',
            'active'         => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** Otras formas de venderlo (caja, media caja...), de la más chica a la más grande */
    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class)->orderBy('units');
    }

    /** Productos con stock en o por debajo del mínimo (y que tienen un mínimo configurado) */
    public function scopeLowStock(Builder $query): void
    {
        $query->where('min_stock', '>', 0)->whereColumn('stock', '<=', 'min_stock');
    }

    /** Busca por nombre o código de barras */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($term) . '%'])
              ->orWhere('barcode', $term)
              ->orWhereHas('presentations', fn (Builder $p) => $p->where('barcode', $term));
        });
    }

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit][0] ?? $this->unit;
    }

    public function sunatUnit(): string
    {
        return self::UNITS[$this->unit][1] ?? 'NIU';
    }

    /** ¿Se vende en fracciones? (kilo, litro) */
    public function isFractional(): bool
    {
        return self::UNITS[$this->unit][2] ?? false;
    }

    public function isLowStock(): bool
    {
        return (float) $this->min_stock > 0 && (float) $this->stock <= (float) $this->min_stock;
    }

    /** Datos que usa el punto de venta (JSON). Cargar antes la relación presentations. */
    public function toPos(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'barcode'       => $this->barcode,
            'price'         => (float) $this->sale_price,
            'stock'         => (float) $this->stock,
            'unit'          => $this->unitLabel(),
            'fractional'    => $this->isFractional(),
            'category'      => $this->category_id,
            'presentations' => $this->presentations->map(fn (ProductPresentation $p) => [
                'id'      => $p->id,
                'name'    => $p->name,
                'units'   => (float) $p->units,
                'price'   => (float) $p->price,
                'barcode' => $p->barcode,
            ])->values()->all(),
        ];
    }
}
