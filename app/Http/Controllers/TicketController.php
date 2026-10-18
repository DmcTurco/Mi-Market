<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopedToCompany;
use App\Models\Sale;

/**
 * Ticket imprimible (80 mm) de una venta, para la empresa y el empleado.
 */
class TicketController extends Controller
{
    use ScopedToCompany;

    public function company(Sale $sale)
    {
        return $this->render($sale);
    }

    public function employee(Sale $sale)
    {
        return $this->render($sale);
    }

    private function render(Sale $sale)
    {
        $this->ensureOwned($sale);
        $sale->load('items', 'company', 'employee');

        return view('shared.ticket', compact('sale'));
    }
}
