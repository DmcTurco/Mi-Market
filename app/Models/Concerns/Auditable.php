<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/**
 * Registra en la bitácora cada vez que el modelo se crea, cambia, elimina o restaura,
 * con los valores de antes y después ("Precio de venta 4.50 → 4.20").
 *
 * El modelo define auditName() ("Producto") y auditLabel() ("Arroz Costeño 1kg").
 * Puede ignorar campos con la propiedad $auditIgnore (p. ej. el stock, que tiene su propio historial).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->writeAudit('created', "Creó {$model->auditName()} «{$model->auditLabel()}»"));

        static::updated(function ($model) {
            $changes = $model->auditChanges();
            if (!$changes) {
                return;
            }

            $summary = collect($changes)->map(function ($values, $field) {
                $label = AuditLog::FIELD_LABELS[$field] ?? $field;
                return $field === 'password' ? "{$label} cambiada" : "{$label}: {$values[0]} → {$values[1]}";
            })->join('; ');

            $model->writeAudit('updated', "Cambió {$model->auditName()} «{$model->auditLabel()}»: {$summary}", $changes);
        });

        static::deleted(fn ($model) => $model->writeAudit('deleted', "Eliminó {$model->auditName()} «{$model->auditLabel()}»"));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($model) => $model->writeAudit('restored', "Recuperó {$model->auditName()} «{$model->auditLabel()}»"));
        }
    }

    /**
     * Campos que cambiaron: [campo => [antes, después]]. Las contraseñas nunca se guardan.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    protected function auditChanges(): array
    {
        $ignore  = array_merge(['created_at', 'updated_at', 'deleted_at', 'remember_token'], $this->auditIgnore ?? []);
        $changes = [];

        foreach (array_keys($this->getChanges()) as $field) {
            if (in_array($field, $ignore, true)) {
                continue;
            }
            // Antes y después con sus casts (decimales "4.50", booleanos sí/no)
            $old = $this->getOriginal($field);
            $new = $this->getAttribute($field);
            $changes[$field] = $field === 'password'
                ? ['***', '***']
                : [$this->auditValue($old), $this->auditValue($new)];
        }

        return $changes;
    }

    private function auditValue(mixed $value): string
    {
        return match (true) {
            $value === null || $value === '' => '(vacío)',
            is_bool($value)                  => $value ? 'sí' : 'no',
            $value instanceof \DateTimeInterface => $value->format('d/m/Y H:i'),
            default                          => (string) $value,
        };
    }

    protected function writeAudit(string $event, string $description, array $changes = []): void
    {
        AuditLog::record(strtolower(class_basename($this)) . '.' . $event, $description, $this, $changes);
    }

    public function auditName(): string
    {
        return mb_strtolower(class_basename($this));
    }

    public function auditLabel(): string
    {
        return (string) ($this->name ?? "#{$this->getKey()}");
    }
}
