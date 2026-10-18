<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta del punto de venta. total = lo que pagó el cliente (IGV incluido).
 */
class Sale extends Model
{
    use SoftDeletes;

    // Estado
    const ACTIVE = 'active';
    const VOIDED = 'voided';

    // Comprobante
    const TICKET  = 'ticket';
    const BOLETA  = 'boleta';
    const FACTURA = 'factura';

    const VOUCHER_TYPES = [
        self::TICKET  => 'Ticket',
        self::BOLETA  => 'Boleta',
        self::FACTURA => 'Factura',
    ];

    // Forma de pago
    const CASH   = 'efectivo';
    const CREDIT = 'fiado';

    const PAYMENT_METHODS = [
        'efectivo' => 'Efectivo',
        'yape'     => 'Yape',
        'plin'     => 'Plin',
        'tarjeta'  => 'Tarjeta',
        'fiado'    => 'Fiado',
    ];

    // SUNAT
    const SUNAT_NOT_APPLICABLE = 'not_applicable';
    const SUNAT_PENDING        = 'pending';
    const SUNAT_ACCEPTED       = 'accepted';
    const SUNAT_REJECTED       = 'rejected';
    const SUNAT_ERROR          = 'error';

    protected $fillable = [
        'company_id',
        'cash_register_id',
        'employee_id',
        'client_id',
        'voucher_type',
        'series',
        'number',
        'customer_name',
        'customer_document_type',
        'customer_document',
        'customer_address',
        'payment_method',
        'amount_received',
        'change',
        'taxable_amount',
        'exonerated_amount',
        'unaffected_amount',
        'gross_total',
        'discount_amount',
        'igv',
        'total',
        'rounding_amount',
        'credit_balance',
        'status',
        'voided_at',
        'void_reason',
        'sunat_status',
        'sunat_code',
        'sunat_message',
        'sunat_hash',
        'sunat_xml_path',
        'sunat_cdr_path',
        'sunat_attempts',
        'sunat_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_received'   => 'decimal:2',
            'change'            => 'decimal:2',
            'taxable_amount'    => 'decimal:2',
            'exonerated_amount' => 'decimal:2',
            'unaffected_amount' => 'decimal:2',
            'gross_total'       => 'decimal:2',
            'discount_amount'   => 'decimal:2',
            'igv'               => 'decimal:2',
            'total'             => 'decimal:2',
            'rounding_amount'   => 'decimal:2',
            'credit_balance'    => 'decimal:2',
            'voided_at'         => 'datetime',
            'sunat_sent_at'     => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->withTrashed();
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class)->withTrashed();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Número completo: B001-00000012 */
    public function voucherNumber(): string
    {
        return $this->series . '-' . str_pad((string) $this->number, 8, '0', STR_PAD_LEFT);
    }

    public function voucherLabel(): string
    {
        return self::VOUCHER_TYPES[$this->voucher_type] ?? $this->voucher_type;
    }

    public function paymentLabel(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function isVoided(): bool
    {
        return $this->status === self::VOIDED;
    }

    /** Lo que realmente se cobró (total con el redondeo del efectivo) */
    public function charged(): float
    {
        return round((float) $this->total - (float) $this->rounding_amount, 2);
    }

    public function isElectronic(): bool
    {
        return in_array($this->voucher_type, [self::BOLETA, self::FACTURA], true);
    }
}
