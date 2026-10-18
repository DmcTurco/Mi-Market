<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\HandlesPurchases;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    use HandlesPurchases;

    public function index()
    {
        $purchases = $this->company()->purchases()
            ->with('employee')
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('company.pages.purchases.index', compact('purchases'));
    }

    public function create()
    {
        return view('company.pages.purchases.create', ['products' => $this->purchaseProducts()]);
    }

    public function store(Request $request, PurchaseService $service)
    {
        $data = $this->validatePurchase($request);

        $purchase = $service->register($this->companyId(), null, $data['items'], $data['supplier'] ?? null);

        return redirect()->route('company.purchases.show', $purchase)
            ->with('success', 'Mercadería registrada. El stock ya se actualizó.');
    }

    public function show(Purchase $purchase)
    {
        return $this->showPurchase($purchase, 'company.pages.purchases.show');
    }
}
