<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Llegó mercadería": un ingreso de stock con sus productos.
 */
class Purchase extends Model
{
    use SoftDeletes;

    const ACTIVE = 'active';
    const VOIDED = 'voided';

    protected $fillable = [
        'company_id',
        'employee_id',
        'cash_movement_id',
        'supplier',
        'total',
        'status',
        'voided_at',
        'void_reason',
    ];

    protected $attributes = [
        'status' => self::ACTIVE,
    ];

    protected function casts(): array
    {
        return [
            'total'     => 'decimal:2',
            'voided_at' => 'datetime',
        ];
    }

    public function isVoided(): bool
    {
        return $this->status === self::VOIDED;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(CashMovement::class);
    }
}
