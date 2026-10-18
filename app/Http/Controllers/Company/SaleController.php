<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ScopedToCompany;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SaleController extends Controller
{
    use ScopedToCompany;

    /**
     * Ventas de un día (hoy por defecto), con filtros por empleado, comprobante y forma de pago.
     */
    public function index(Request $request)
    {
        $date = rescue(fn () => Carbon::parse($request->input('fecha', today())), today(), false)->startOfDay();

        $query = $this->company()->sales()
            ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
            ->when($request->filled('empleado'), fn ($q) => $q->where('employee_id', $request->empleado))
            ->when($request->filled('pago'), fn ($q) => $q->where('payment_method', $request->pago));

        $totals = (clone $query)->where('status', Sale::ACTIVE)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $sales     = $query->with('employee')->latest()->paginate(30)->withQueryString();
        $employees = $this->company()->employees()->orderBy('name')->get(['id', 'name']);

        return view('company.pages.sales.index', compact('sales', 'employees', 'date', 'totals'));
    }

    public function show(Sale $sale)
    {
        $this->ensureOwned($sale);
        $sale->load('items', 'employee', 'client', 'cashRegister');

        return view('company.pages.sales.show', compact('sale'));
    }

    public function void(Request $request, Sale $sale, SaleService $service)
    {
        $this->ensureOwned($sale);
        $data = $request->validate(['reason' => 'required|string|max:255'], ['reason.required' => 'Escribe por qué se anula la venta.']);

        $service->void($sale, $data['reason']);

        return redirect()->route('company.sales.show', $sale)
            ->with('success', 'Venta ' . $sale->voucherNumber() . ' anulada. El stock se devolvió.');
    }
}
