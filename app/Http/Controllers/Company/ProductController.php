<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Concerns\ScopedToCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ProductRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    use ScopedToCompany;

    public function index(Request $request)
    {
        $products = $this->company()->products()
            ->with('category')
            ->search($request->buscar)
            ->when($request->filled('categoria'), fn ($q) => $q->where('category_id', $request->categoria))
            ->when($request->filtro === 'pocos', fn ($q) => $q->lowStock())
            ->when($request->filtro === 'inactivos', fn ($q) => $q->where('active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = $this->company()->categories()->orderBy('name')->get();
        $lowCount   = $this->company()->products()->lowStock()->count();

        return view('company.pages.products.index', compact('products', 'categories', 'lowCount'));
    }

    public function create()
    {
        return view('company.pages.products.form', [
            'categories' => $this->company()->categories()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * El stock inicial se registra como corrección de stock para que quede en el historial.
     */
    public function store(ProductRequest $request, StockService $stock)
    {
        $product = DB::transaction(function () use ($request, $stock) {
            $product = $this->company()->products()->create($request->productData() + ['stock' => 0]);

            $initial = (float) $request->input('stock', 0);
            if ($initial > 0) {
                $stock->move($product->id, $initial, StockMovement::ADJUSTMENT, null, 'Stock inicial');
            }

            return $product;
        });

        return redirect()->route($request->boolean('another') ? 'company.products.create' : 'company.products.index')
            ->with('success', "Producto «{$product->name}» registrado.");
    }

    public function edit(Product $product)
    {
        $this->ensureOwned($product);

        return view('company.pages.products.form', [
            'product'    => $product,
            'categories' => $this->company()->categories()
                ->where(fn ($q) => $q->where('active', true)->orWhere('id', $product->category_id))
                ->orderBy('name')->get(),
        ]);
    }

    /**
     * Si cambió el stock, la diferencia queda en el historial como corrección.
     */
    public function update(ProductRequest $request, Product $product, StockService $stock)
    {
        $this->ensureOwned($product);

        DB::transaction(function () use ($request, $product, $stock) {
            $product->update($request->productData());

            $difference = round((float) $request->input('stock', $product->stock) - (float) $product->fresh()->stock, 3);
            if ($difference != 0) {
                $stock->move($product->id, $difference, StockMovement::ADJUSTMENT, null, 'Corrección manual');
            }
        });

        return redirect()->route('company.products.index')
            ->with('success', "Producto «{$product->name}» actualizado.");
    }

    public function destroy(Product $product)
    {
        $this->ensureOwned($product);

        $product->delete();

        return redirect()->back()
            ->with('success', "Producto «{$product->name}» eliminado.");
    }

    /** Historial de movimientos de stock del producto */
    public function history(Product $product)
    {
        $this->ensureOwned($product);

        $movements = $product->stockMovements()->latest('id')->paginate(30);

        return view('company.pages.products.history', compact('product', 'movements'));
    }
}
