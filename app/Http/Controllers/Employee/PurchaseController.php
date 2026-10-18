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
        $canChange = $this->canChange($purchase);

        return $this->showPurchase($purchase, 'employee.pages.purchases.show', [
            'editUrl' => $canChange ? route('employee.purchases.edit', $purchase) : null,
            'voidUrl' => $canChange ? route('employee.purchases.void', $purchase) : null,
        ]);
    }

    public function edit(Purchase $purchase)
    {
        $this->ensureOwned($purchase);
        abort_unless($this->canChange($purchase) && !$purchase->isVoided(), 403, 'Solo puedes corregir la mercadería que registraste hoy. Pídeselo al encargado.');
        $purchase->load('items');

        return view('employee.pages.purchases.edit', [
            'purchase' => $purchase,
            'products' => $this->purchaseProducts(),
            'items'    => $this->purchaseFormItems($purchase),
        ]);
    }

    public function update(Request $request, Purchase $purchase, PurchaseService $service)
    {
        $this->ensureOwned($purchase);
        abort_unless($this->canChange($purchase), 403, 'Solo puedes corregir la mercadería que registraste hoy. Pídeselo al encargado.');
        $data = $this->validatePurchase($request);

        $service->update($purchase, $data['items'], $data['supplier'] ?? null);

        return redirect()->route('employee.purchases.show', $purchase)
            ->with('success', 'Ingreso corregido. El stock ya se ajustó.');
    }

    /** El empleado corrige su propio error del día; lo demás lo corrige la empresa */
    public function void(Request $request, Purchase $purchase, PurchaseService $service)
    {
        $this->ensureOwned($purchase);
        abort_unless($this->canChange($purchase), 403, 'Solo puedes anular la mercadería que registraste hoy. Pídeselo al encargado.');

        $service->void($purchase, $this->validateVoid($request));

        return redirect()->route('employee.purchases.show', $purchase)
            ->with('success', 'Ingreso anulado. El stock volvió a como estaba; ya puedes registrarlo bien.');
    }

    private function canChange(Purchase $purchase): bool
    {
        return (int) $purchase->employee_id === (int) auth()->id() && $purchase->created_at->isToday();
    }
}
