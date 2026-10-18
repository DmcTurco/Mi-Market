<?php

use App\Support\Qty;

if (!function_exists('money')) {
    /** 1234.5 → "S/ 1,234.50" */
    function money(float|string|null $amount): string
    {
        return Qty::money($amount);
    }
}

if (!function_exists('qty')) {
    /** 3.000 → "3", 1.250 → "1.25" */
    function qty(float|string|null $quantity): string
    {
        return Qty::format($quantity);
    }
}
