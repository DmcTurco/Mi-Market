<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CashMovement;
use App\Support\Qty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gastos (pagó algo con dinero de la caja) e ingresos (le dieron dinero que no es una venta).
 */
class CashMovementController extends Controller
{
    public function index(Request $request)
    {
        $register  = $request->attributes->get('cash_register');
        $movements = $register->movements()->latest()->get();

        return view('employee.pages.cash.movements', compact('register', 'movements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'    => ['required', Rule::in([CashMovement::INCOME, CashMovement::EXPENSE])],
            'concept' => 'required|string|max:150',
            'amount'  => 'required|numeric|min:0.1|max:999999',
        ], [
            'concept.required' => 'Escribe en qué se gastó o de dónde vino el dinero.',
            'amount.required'  => 'Escribe el monto.',
            'amount.min'       => 'El monto debe ser mayor que cero.',
        ]);

        $register = $request->attributes->get('cash_register');

        $movement = $register->movements()->create($data + [
            'company_id'  => $register->company_id,
            'employee_id' => $register->employee_id,
        ]);

        $isExpense = $data['type'] === CashMovement::EXPENSE;
        AuditLog::record($isExpense ? 'cash.expense' : 'cash.income',
            ($isExpense ? 'Sacó ' : 'Ingresó ') . Qty::money($data['amount']) . ($isExpense ? ' de' : ' a') . " la caja: {$data['concept']}",
            $movement, ['monto' => (float) $data['amount'], 'concepto' => $data['concept']]);

        return redirect()->route('employee.cash.movements')
            ->with('success', $data['type'] === CashMovement::EXPENSE ? 'Gasto registrado.' : 'Ingreso registrado.');
    }
}
