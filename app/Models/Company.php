<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Empresa (tienda cliente del sistema). Entra al panel /company con su correo.
 * Todo lo que registra (empleados, categorías, productos) lleva su company_id.
 */
class Company extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'ruc',
        'address',
        'phone',
        'email',
        'password',
        'cash_rounding',
        'rounding_favor',
        'allow_discounts',
        'max_discount_percent',
    ];

    const ROUND_FOR_CUSTOMER = 'customer';
    const ROUND_FOR_BUSINESS = 'business';

    /** Mismos valores por defecto que la base de datos */
    protected $attributes = [
        'cash_rounding'        => true,
        'rounding_favor'       => self::ROUND_FOR_CUSTOMER,
        'allow_discounts'      => true,
        'max_discount_percent' => 20,
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'cash_rounding'        => 'boolean',
            'allow_discounts'      => 'boolean',
            'max_discount_percent' => 'integer',
        ];
    }

    /**
     * Redondeo del efectivo al múltiplo de S/ 0.10 (no circulan monedas de 1 ni 5 céntimos).
     * Devuelve lo que se descuenta del total al cobrar: positivo a favor del cliente (4.27 → 4.20 = +0.07),
     * negativo a favor del negocio (4.27 → 4.30 = −0.03). El comprobante mantiene el total exacto.
     */
    public function cashRounding(float $total): float
    {
        $rest = (int) round($total * 100) % 10;

        if (!$this->cash_rounding || $rest === 0) {
            return 0.0;
        }

        return $this->rounding_favor === self::ROUND_FOR_BUSINESS
            ? -round((10 - $rest) / 100, 2)
            : round($rest / 100, 2);
    }

    /**
     * ¿Puede emitir boleta y factura electrónica? Se activa al configurar SUNAT (etapa de facturación).
     * Mientras tanto el punto de venta solo emite tickets.
     */
    public function sunatEnabled(): bool
    {
        return false;
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }
}
