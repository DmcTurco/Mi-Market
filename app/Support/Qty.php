<?php

namespace App\Support;

/**
 * Formato de cantidades y montos para mostrar al usuario.
 */
class Qty
{
    /** 3.000 → "3", 1.250 → "1.25" */
    public static function format(float|string|null $quantity): string
    {
        return rtrim(rtrim(number_format((float) $quantity, 3, '.', ''), '0'), '.') ?: '0';
    }

    /** 1234.5 → "S/ 1,234.50" */
    public static function money(float|string|null $amount): string
    {
        return 'S/ ' . number_format((float) $amount, 2);
    }
}
