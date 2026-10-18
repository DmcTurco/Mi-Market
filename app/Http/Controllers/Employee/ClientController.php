<?php

namespace App\Http\Controllers\Employee;

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
        return view('employee.pages.clients.index', ['clients' => $this->clientList($request)]);
    }

    public function show(Client $client)
    {
        return view('employee.pages.clients.show', $this->clientDetail($client));
    }

    /** Abono cobrado por el empleado: entra a su caja */
    public function pay(Request $request, Client $client, CreditService $credit)
    {
        $this->ensureOwned($client);
        $data = $this->validatePayment($request);

        $credit->pay($client, (float) $data['amount'], $data['payment_method'], auth()->user(), $request->attributes->get('cash_register'));

        return redirect()->route('employee.clients.show', $client)
            ->with('success', 'Pago de ' . Qty::money($data['amount']) . ' registrado.');
    }

    /** Buscador de clientes del punto de venta (JSON) */
    public function search(Request $request)
    {
        $clients = $this->company()->clients()
            ->withDebt()
            ->search($request->q)
            ->orderBy('name')
            ->take(8)
            ->get()
            ->map(fn (Client $c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'document' => $c->document_number,
                'address'  => $c->address,
                'phone'    => $c->phone,
                'debt'     => round((float) $c->debt_total, 2),
            ]);

        return response()->json($clients);
    }
}
