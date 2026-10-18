<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Abrir y cerrar la caja del turno.
 */
class CashRegisterController extends Controller
{
    /** "¿Con cuánto dinero empiezas?" */
    public function create()
    {
        if (auth()->user()->openCashRegister()) {
            return redirect()->route('employee.home');
        }

        return view('employee.pages.cash.open');
    }

    public function store(Request $request)
    {
        $data = $request->validate(
            ['opening_amount' => 'required|numeric|min:0|max:999999'],
            ['opening_amount.required' => 'Escribe con cuánto dinero empiezas (puede ser 0).', 'opening_amount.numeric' => 'Escribe solo números, por ejemplo 50']
        );

        $employee = auth()->user();

        DB::transaction(function () use ($employee, $data) {
            // Evita dos cajas abiertas si se hace doble clic
            if ($employee->cashRegisters()->open()->lockForUpdate()->exists()) {
                return;
            }

            $employee->cashRegisters()->create([
                'company_id'     => $employee->company_id,
                'opening_amount' => $data['opening_amount'],
                'opened_at'      => now(),
            ]);
        });

        return redirect()->route('employee.home')
            ->with('success', '¡Caja abierta! Ya puedes vender.');
    }

    /** Cuadre: cuánto debería haber y cuánto contó */
    public function edit(Request $request)
    {
        $register = $request->attributes->get('cash_register');

        return view('employee.pages.cash.close', [
            'register' => $register,
            'summary'  => $register->summary(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(
            [
                'closing_amount' => 'required|numeric|min:0|max:999999',
                'notes'          => 'nullable|string|max:500',
            ],
            ['closing_amount.required' => 'Cuenta el dinero de la caja y escribe el total.', 'closing_amount.numeric' => 'Escribe solo números, por ejemplo 350.50']
        );

        /** @var CashRegister $register */
        $register = $request->attributes->get('cash_register');
        $expected = $register->summary()['expected_cash'];
        $counted  = round((float) $data['closing_amount'], 2);

        $register->update([
            'expected_amount' => $expected,
            'closing_amount'  => $counted,
            'difference'      => round($counted - $expected, 2),
            'notes'           => $data['notes'] ?? null,
            'closed_at'       => now(),
        ]);

        return redirect()->route('employee.home')
            ->with('success', 'Caja cerrada. ¡Buen trabajo!');
    }
}
