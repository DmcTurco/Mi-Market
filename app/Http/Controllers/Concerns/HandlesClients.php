<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Client;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fiados: lista de clientes con deuda y su detalle. Compartido por la empresa y el empleado.
 */
trait HandlesClients
{
    use ScopedToCompany;

    protected function clientList(Request $request)
    {
        return $this->company()->clients()
            ->withDebt()
            ->search($request->buscar)
            ->when($request->filtro !== 'todos', fn ($q) => $q->whereHas('sales', fn ($s) => $s->where('status', Sale::ACTIVE)->where('credit_balance', '>', 0)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
    }

    protected function clientDetail(Client $client): array
    {
        $this->ensureOwned($client);

        return [
            'client'   => $client,
            'debt'     => $client->debt(),
            'pending'  => $client->pendingSales()->with('items')->orderBy('created_at')->orderBy('id')->get(),
            'payments' => $client->creditPayments()->with('employee')->latest()->take(20)->get(),
        ];
    }

    protected function validatePayment(Request $request): array
    {
        return $request->validate([
            'amount'         => 'required|numeric|min:0.1|max:999999',
            'payment_method' => ['required', Rule::in(array_keys(array_diff_key(Sale::PAYMENT_METHODS, [Sale::CREDIT => true])))],
        ], [
            'amount.required' => 'Escribe cuánto está pagando.',
            'amount.numeric'  => 'Escribe solo números, por ejemplo 20',
        ]);
    }
}
