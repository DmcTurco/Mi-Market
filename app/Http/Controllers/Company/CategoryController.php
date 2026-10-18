<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CategoryRequest;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Categorías de la empresa. Crear y editar se hacen en modales (Alpine) dentro del listado.
 */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->buscar);

        $categories = $this->company()->categories()
            ->withCount('products')
            ->when($search !== '', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($search) . '%']))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('company.pages.categories.index', compact('categories'));
    }

    public function store(CategoryRequest $request)
    {
        $category = $this->company()->categories()->create([
            'name'   => $request->name,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->route('company.categories.index')
            ->with('success', "Categoría «{$category->name}» registrada.");
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $this->authorizeCategory($category);

        $category->update([
            'name'   => $request->name,
            'active' => $request->boolean('active'),
        ]);

        return redirect()->back()
            ->with('success', "Categoría «{$category->name}» actualizada.");
    }

    /**
     * Elimina la categoría (soft delete). No se puede si todavía tiene productos.
     */
    public function destroy(Category $category)
    {
        $this->authorizeCategory($category);

        if ($category->products()->exists()) {
            return redirect()->back()
                ->with('error', "La categoría «{$category->name}» tiene productos. Muévelos a otra categoría antes de eliminarla.");
        }

        $category->delete();

        return redirect()->back()
            ->with('success', "Categoría «{$category->name}» eliminada.");
    }

    private function company(): Company
    {
        return auth()->guard('company')->user();
    }

    /**
     * Una empresa solo puede tocar sus propias categorías.
     */
    private function authorizeCategory(Category $category): void
    {
        abort_unless((int) $category->company_id === (int) $this->company()->id, 404);
    }
}
