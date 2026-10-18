<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora: quién hizo qué, sobre qué y cuándo. Solo se escribe: nunca se edita ni se borra.
 *
 * Se llena de dos formas:
 *  - Automática: los modelos con el trait Auditable registran al crear, cambiar o eliminar (con antes → después).
 *  - Manual: AuditLog::record() en las acciones del negocio (ventas, anulaciones, caja, mercadería, ingresos al sistema).
 */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    /** Acciones agrupadas para filtrar en la pantalla */
    const GROUPS = [
        'Ventas'          => ['sale.created', 'sale.discount', 'sale.void'],
        'Caja y fiados'   => ['cash.open', 'cash.close', 'cash.expense', 'cash.income', 'credit.payment'],
        'Mercadería'      => ['purchase.created', 'purchase.updated', 'purchase.void', 'stock.adjust'],
        'Productos'       => ['product.created', 'product.updated', 'product.deleted', 'productpresentation.created', 'productpresentation.updated', 'productpresentation.deleted', 'category.created', 'category.updated', 'category.deleted'],
        'Personal'        => ['employee.created', 'employee.updated', 'employee.deleted', 'client.created', 'client.updated', 'client.deleted', 'company.updated'],
        'Ingresos'        => ['login', 'logout', 'login.failed'],
    ];

    /** Nombre de los campos para que el cambio se entienda: "Precio de venta 4.50 → 4.20" */
    const FIELD_LABELS = [
        'name'                 => 'Nombre',
        'sale_price'           => 'Precio de venta',
        'purchase_price'       => 'Costo',
        'min_stock'            => 'Aviso de stock',
        'barcode'              => 'Código de barras',
        'active'               => 'Activo',
        'category_id'          => 'Categoría',
        'unit'                 => 'Se vende por',
        'igv_affectation'      => 'IGV',
        'units'                => 'Unidades',
        'price'                => 'Precio',
        'email'                => 'Correo',
        'password'             => 'Contraseña',
        'phone'                => 'Teléfono',
        'address'              => 'Dirección',
        'ruc'                  => 'RUC',
        'document_number'      => 'Documento',
        'allow_discounts'      => 'Permitir descuentos',
        'max_discount_percent' => 'Descuento máximo %',
        'cash_rounding'        => 'Redondeo del efectivo',
        'rounding_favor'       => 'Redondeo a favor de',
    ];

    protected $fillable = [
        'company_id',
        'actor_type',
        'actor_id',
        'actor_name',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'description',
        'changes',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'changes'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Registra una acción. Nunca rompe la operación principal: si la bitácora falla, se reporta y sigue.
     *
     * @param  array<string, mixed>  $changes  datos útiles: antes/después, montos, motivo
     * @param  ?object  $actor  quién lo hizo (por defecto el usuario conectado)
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $changes = [], ?object $actor = null, ?int $companyId = null): void
    {
        try {
            $actor ??= auth()->user();
            [$actorType, $actorCompany] = match (true) {
                $actor instanceof Employee => ['employee', $actor->company_id],
                $actor instanceof Company  => ['company', $actor->id],
                $actor instanceof Admin    => ['admin', null],
                default                    => ['system', null],
            };

            static::create([
                'company_id'    => $companyId ?? $subject?->company_id ?? ($subject instanceof Company ? $subject->id : null) ?? $actorCompany,
                'actor_type'    => $actorType,
                'actor_id'      => $actor?->id,
                'actor_name'    => $actor?->name,
                'action'        => $action,
                'subject_type'  => $subject ? class_basename($subject) : null,
                'subject_id'    => $subject?->getKey(),
                'subject_label' => $subject && method_exists($subject, 'auditLabel') ? mb_substr($subject->auditLabel(), 0, 150) : null,
                'description'   => mb_substr($description, 0, 255),
                'changes'       => $changes ?: null,
                'ip'            => request()->ip(),
                'user_agent'    => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function scopeForCompany(Builder $query, int $companyId): void
    {
        $query->where('company_id', $companyId);
    }

    public function actorLabel(): string
    {
        // Un intento fallido lo hizo alguien que no sabemos quién es
        if ($this->actor_type === 'system' && $this->action === 'login.failed') {
            return 'Desconocido';
        }

        $who = ['admin' => 'Administrador', 'company' => 'Empresa', 'employee' => 'Empleado', 'system' => 'Sistema'][$this->actor_type] ?? $this->actor_type;

        return $this->actor_name ? "{$this->actor_name} ({$who})" : $who;
    }

    /** Ícono y color por tipo de acción, para que la lista se lea de un vistazo */
    public function style(): array
    {
        return match (true) {
            str_ends_with($this->action, '.void'), str_ends_with($this->action, '.deleted'), $this->action === 'login.failed'
                => ['fa-triangle-exclamation', 'text-red-600 bg-red-50'],
            str_starts_with($this->action, 'sale.discount'), $this->action === 'stock.adjust', $this->action === 'cash.close'
                => ['fa-circle-exclamation', 'text-amber-700 bg-amber-50'],
            str_starts_with($this->action, 'login'), $this->action === 'logout'
                => ['fa-right-to-bracket', 'text-slate-500 bg-slate-100'],
            str_ends_with($this->action, '.updated')
                => ['fa-pen', 'text-sky-700 bg-sky-50'],
            default
                => ['fa-check', 'text-emerald-700 bg-emerald-50'],
        };
    }
}
