<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

/**
 * Correlativo de comprobantes por empresa y tipo (T001, B001, F001).
 */
class DocumentSeries extends Model
{
    use SoftDeletes;

    const DEFAULT_SERIES = [
        Sale::TICKET  => 'T001',
        Sale::BOLETA  => 'B001',
        Sale::FACTURA => 'F001',
    ];

    protected $fillable = [
        'company_id',
        'voucher_type',
        'series',
        'last_number',
    ];

    /**
     * Reserva el siguiente número. Debe llamarse DENTRO de una transacción: bloquea la fila
     * para que dos cajas no saquen el mismo número.
     *
     * @return array{0: string, 1: int} [serie, número]
     */
    public static function next(int $companyId, string $voucherType): array
    {
        $series = static::where('company_id', $companyId)
            ->where('voucher_type', $voucherType)
            ->lockForUpdate()
            ->first();

        if (!$series) {
            static::firstOrCreate(
                ['company_id' => $companyId, 'voucher_type' => $voucherType],
                ['series' => self::DEFAULT_SERIES[$voucherType], 'last_number' => 0],
            );

            $series = static::where('company_id', $companyId)
                ->where('voucher_type', $voucherType)
                ->lockForUpdate()
                ->first();
        }

        $series->increment('last_number');

        return [$series->series, (int) $series->last_number];
    }
}
