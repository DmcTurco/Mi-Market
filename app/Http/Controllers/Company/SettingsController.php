<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Configuración de ventas de la empresa: descuentos del cajero y redondeo del efectivo.
 */
class SettingsController extends Controller
{
    public function edit()
    {
        return view('company.pages.settings', ['company' => auth()->guard('company')->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'allow_discounts'      => 'nullable|boolean',
            'max_discount_percent' => 'required|integer|min:1|max:100',
            'cash_rounding'        => 'nullable|boolean',
            'rounding_favor'       => ['required', Rule::in([Company::ROUND_FOR_CUSTOMER, Company::ROUND_FOR_BUSINESS])],
        ], [
            'max_discount_percent.required' => 'Escribe el descuento máximo (por ejemplo 10).',
            'max_discount_percent.max'      => 'El descuento máximo no puede pasar de 100%.',
        ]);

        auth()->guard('company')->user()->update([
            'allow_discounts'      => $request->boolean('allow_discounts'),
            'max_discount_percent' => $data['max_discount_percent'],
            'cash_rounding'        => $request->boolean('cash_rounding'),
            'rounding_favor'       => $data['rounding_favor'],
        ]);

        return redirect()->route('company.settings.edit')
            ->with('success', 'Configuración guardada. Los cajeros la verán al recargar la pantalla de venta.');
    }
}
