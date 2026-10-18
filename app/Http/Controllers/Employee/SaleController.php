<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

/**
 * Ventas de la caja abierta del empleado ("Mis ventas de hoy").
 */
class SaleController extends Controller
{
    public function index(Request $request)
    {
        $register = $request->attributes->get('cash_register');
        $sales    = $register->sales()->latest()->get();

        return view('employee.pages.sales.index', compact('register', 'sales'));
    }

    public function show(Request $request, Sale $sale)
    {
        $this->ensureOwnRegister($request, $sale);
        $sale->load('items', 'client');

        return view('employee.pages.sales.show', compact('sale'));
    }

    public function void(Request $request, Sale $sale, SaleService $service)
    {
        $this->ensureOwnRegister($request, $sale);
        $data = $request->validate(['reason' => 'required|string|max:255'], ['reason.required' => 'Escribe por qué se anula la venta.']);

        $service->void($sale, $data['reason']);

        return redirect()->route('employee.sales.index')
            ->with('success', 'Venta ' . $sale->voucherNumber() . ' anulada. Los productos volvieron al stock.');
    }

    /** El empleado solo ve y anula las ventas de su caja abierta */
    private function ensureOwnRegister(Request $request, Sale $sale): void
    {
        abort_unless((int) $sale->cash_register_id === (int) $request->attributes->get('cash_register')->id, 404);
    }
}
