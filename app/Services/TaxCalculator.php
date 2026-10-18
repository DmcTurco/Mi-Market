<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Separa el IGV de una venta. A diferencia de Farmacia, aquí los precios son FINALES
 * (IGV incluido): lo que el cliente ve en la góndola es lo que paga.
 *
 * Por línea: total = precio × cantidad; si es gravada, base = total ÷ 1.18 e IGV = total − base.
 * Afectaciones (SUNAT catálogo 07): 10 gravado, 20 exonerado, 30 inafecto.
 */
class TaxCalculator
{
    const IGV_RATE = 0.18;

    const GRAVADO   = '10';
    const EXONERADO = '20';
    const INAFECTO  = '30';

    /**
     * @param  array<int, array{price: float|string, quantity: float|string, affectation: string}>  $items
     * @return array{lines: array<int, array{total: float, base: float, igv: float}>,
     *               taxable: float, exonerated: float, unaffected: float, igv: float, total: float}
     */
    public function calculate(array $items): array
    {
        $lines = [];
        $taxable = $exonerated = $unaffected = $igv = $total = 0.0;

        foreach ($items as $index => $item) {
            $lineTotal = round((float) $item['price'] * (float) $item['quantity'], 2);

            if ($item['affectation'] === self::GRAVADO) {
                $base    = round($lineTotal / (1 + self::IGV_RATE), 2);
                $lineIgv = round($lineTotal - $base, 2);
                $taxable += $base;
            } else {
                $base    = $lineTotal;
                $lineIgv = 0.0;
                match ($item['affectation']) {
                    self::EXONERADO => $exonerated += $base,
                    self::INAFECTO  => $unaffected += $base,
                    default         => throw new InvalidArgumentException("Afectación de IGV no válida: {$item['affectation']}"),
                };
            }

            $igv   += $lineIgv;
            $total += $lineTotal;
            $lines[$index] = ['total' => $lineTotal, 'base' => $base, 'igv' => $lineIgv];
        }

        return [
            'lines'      => $lines,
            'taxable'    => round($taxable, 2),
            'exonerated' => round($exonerated, 2),
            'unaffected' => round($unaffected, 2),
            'igv'        => round($igv, 2),
            'total'      => round($total, 2),
        ];
    }
}
