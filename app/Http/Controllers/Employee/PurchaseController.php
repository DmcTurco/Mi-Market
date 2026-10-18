<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Concerns\HandlesPurchases;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    use HandlesPurchases;

    public function create()
    {
        return view('employee.pages.purchases.create', [
            'products'    => $this->purchaseProducts(),
            'hasRegister' => auth()->user()->openCashRegister() !== null,
        ]);
    }

    /**
     * Si marcó "lo pagué con dinero de la caja", el total sale de su caja como gasto.
     */
    public function store(Request $request, PurchaseService $service)
    {
        $data     = $this->validatePurchase($request);
        $employee = auth()->user();
        $paidFrom = $request->boolean('paid_from_cash') ? $employee->openCashRegister() : null;

        $purchase = $service->register($this->companyId(), $employee, $data['items'], $data['supplier'] ?? null, $paidFrom);

        return redirect()->route('employee.purchases.show', $purchase)
            ->with('success', 'Mercadería registrada. El stock ya se actualizó.');
    }

    public function show(Purchase $purchase)
    {
        return $this->showPurchase($purchase, 'employee.pages.purchases.show');
    }
}
