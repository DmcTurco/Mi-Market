<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cliente de la tienda (fiado, boleta con DNI o factura con RUC).
 */
class Client extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'document_type',
        'document_number',
        'phone',
        'address',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }

    /** Ventas fiadas que todavía tienen saldo por pagar */
    public function pendingSales(): HasMany
    {
        return $this->sales()->where('status', Sale::ACTIVE)->where('credit_balance', '>', 0);
    }

    /** Clientes que deben algo, con su deuda total en debt_total */
    public function scopeWithDebt(Builder $query): void
    {
        $query->withSum(['sales as debt_total' => fn ($q) => $q->where('status', Sale::ACTIVE)], 'credit_balance');
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $query->where(function (Builder $q) use ($term) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($term) . '%'])
              ->orWhere('document_number', $term)
              ->orWhere('phone', $term);
        });
    }

    public function debt(): float
    {
        return round((float) $this->pendingSales()->sum('credit_balance'), 2);
    }
}
