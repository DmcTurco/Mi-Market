<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ScopedToCompany;
use App\Http\Controllers\Controller;
use App\Models\CashRegister;

/**
 * Cajas de los empleados: las abiertas ahora y el cuadre de las cerradas.
 */
class CashRegisterController extends Controller
{
    use ScopedToCompany;

    public function index()
    {
        $open = $this->company()->cashRegisters()->open()->with('employee')->latest('opened_at')->get();

        $closed = $this->company()->cashRegisters()
            ->whereNotNull('closed_at')
            ->with('employee')
            ->latest('closed_at')
            ->paginate(20);

        return view('company.pages.cash-registers.index', compact('open', 'closed'));
    }

    public function show(CashRegister $cashRegister)
    {
        $this->ensureOwned($cashRegister);
        $cashRegister->load('employee');

        return view('company.pages.cash-registers.show', [
            'register'  => $cashRegister,
            'summary'   => $cashRegister->summary(),
            'sales'     => $cashRegister->sales()->latest()->get(),
            'movements' => $cashRegister->movements()->latest()->get(),
            'payments'  => $cashRegister->creditPayments()->with('client')->latest()->get(),
        ]);
    }
}
