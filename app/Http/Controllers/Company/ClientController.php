<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\HandlesClients;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\CreditService;
use App\Support\Qty;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use HandlesClients;

    public function index(Request $request)
    {
        return view('company.pages.clients.index', ['clients' => $this->clientList($request)]);
    }

    public function show(Client $client)
    {
        return view('company.pages.clients.show', $this->clientDetail($client));
    }

    /** Abono recibido por la empresa (no pasa por ninguna caja) */
    public function pay(Request $request, Client $client, CreditService $credit)
    {
        $this->ensureOwned($client);
        $data = $this->validatePayment($request);

        $credit->pay($client, (float) $data['amount'], $data['payment_method']);

        return redirect()->route('company.clients.show', $client)
            ->with('success', 'Pago de ' . Qty::money($data['amount']) . ' registrado.');
    }
}
