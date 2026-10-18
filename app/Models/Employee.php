<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Empleado (cajero) de una empresa. Entra al panel /employee con su correo.
 * Si la empresa lo desactiva (active = false) ya no puede iniciar sesión.
 */
class Employee extends Authenticatable
{
    use Auditable, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    /** Caja abierta del empleado (null si no abrió caja) */
    public function openCashRegister(): ?CashRegister
    {
        return $this->cashRegisters()->open()->latest('opened_at')->first();
    }

    public function auditName(): string
    {
        return 'al empleado';
    }
}
