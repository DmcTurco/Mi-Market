<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Lista las empresas (tiendas clientes) con cuántos empleados y productos tienen.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->buscar);

        $companies = Company::withCount(['employees', 'products'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $like = '%' . mb_strtolower($search) . '%';
                    $q->whereRaw('LOWER(name) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                      ->orWhere('ruc', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pages.companies.index', compact('companies'));
    }

    /**
     * Formulario para registrar una empresa nueva.
     */
    public function create()
    {
        return view('admin.pages.companies.form');
    }

    /**
     * Registra la empresa con su usuario de acceso al panel /company.
     */
    public function store(CompanyRequest $request)
    {
        $company = Company::create($request->safe()->only(['name', 'ruc', 'address', 'phone', 'email', 'password']));

        return redirect()->route('admin.companies.index')
            ->with('success', "Empresa «{$company->name}» creada. Ya puede entrar con {$company->email} en /company/login.");
    }

    /**
     * Formulario para editar los datos de una empresa.
     */
    public function edit(Company $company)
    {
        $company->loadCount(['employees', 'products']);

        return view('admin.pages.companies.form', compact('company'));
    }

    /**
     * Actualiza la empresa. La contraseña solo cambia si se escribe una nueva.
     */
    public function update(CompanyRequest $request, Company $company)
    {
        $data = $request->safe()->only(['name', 'ruc', 'address', 'phone', 'email']);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $company->update($data);

        return redirect()->route('admin.companies.edit', $company)
            ->with('success', "Empresa «{$company->name}» actualizada.");
    }
}
