<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gasto o ingreso de efectivo en la caja que no es una venta.
 */
class CashMovement extends Model
{
    const INCOME  = 'income';
    const EXPENSE = 'expense';

    protected $fillable = [
        'company_id',
        'cash_register_id',
        'employee_id',
        'type',
        'concept',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isExpense(): bool
    {
        return $this->type === self::EXPENSE;
    }
}
