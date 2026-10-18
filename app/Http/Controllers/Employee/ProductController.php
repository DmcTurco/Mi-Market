<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Concerns\ScopedToCompany;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Consultar precios y stock (solo lectura).
 */
class ProductController extends Controller
{
    use ScopedToCompany;

    public function index(Request $request)
    {
        $products = $this->company()->products()
            ->where('active', true)
            ->with('presentations')
            ->search($request->buscar)
            ->when($request->filtro === 'pocos', fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('employee.pages.products', compact('products'));
    }
}
