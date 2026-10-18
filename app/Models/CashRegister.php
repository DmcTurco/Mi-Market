<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Caja de un turno. closed_at = null mientras está abierta.
 *
 * Efectivo esperado al cerrar = apertura + ventas en efectivo + abonos en efectivo + otros ingresos − gastos.
 */
class CashRegister extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'opening_amount',
        'expected_amount',
        'closing_amount',
        'difference',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_amount'  => 'decimal:2',
            'expected_amount' => 'decimal:2',
            'closing_amount'  => 'decimal:2',
            'difference'      => 'decimal:2',
            'opened_at'       => 'datetime',
            'closed_at'       => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('closed_at');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /**
     * Resumen del turno: ventas por forma de pago, abonos, gastos, ingresos y efectivo esperado.
     *
     * @return array{sales_count: int, sales_total: float, by_method: array<string, float>, credit_payments: float,
     *               credit_payments_cash: float, income: float, expense: float, expected_cash: float}
     */
    public function summary(): array
    {
        $byMethod = array_fill_keys(array_keys(Sale::PAYMENT_METHODS), 0.0);
        $rows = $this->sales()->where('status', Sale::ACTIVE)
            ->selectRaw('payment_method, SUM(total) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $byMethod[$row->payment_method] = round((float) $row->total, 2);
            $count += (int) $row->count;
        }

        $creditPayments     = (float) $this->creditPayments()->sum('amount');
        $creditPaymentsCash = (float) $this->creditPayments()->where('payment_method', Sale::CASH)->sum('amount');
        $income  = (float) $this->movements()->where('type', CashMovement::INCOME)->sum('amount');
        $expense = (float) $this->movements()->where('type', CashMovement::EXPENSE)->sum('amount');

        return [
            'sales_count'          => $count,
            'sales_total'          => round(array_sum($byMethod), 2),
            'by_method'            => $byMethod,
            'credit_payments'      => round($creditPayments, 2),
            'credit_payments_cash' => round($creditPaymentsCash, 2),
            'income'               => round($income, 2),
            'expense'              => round($expense, 2),
            'expected_cash'        => round((float) $this->opening_amount + $byMethod[Sale::CASH] + $creditPaymentsCash + $income - $expense, 2),
        ];
    }
}
