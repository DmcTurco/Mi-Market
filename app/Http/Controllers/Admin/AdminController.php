<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;

class AdminController extends Controller
{
    /**
     * Dashboard del administrador: resumen de todo el sistema.
     */
    public function index()
    {
        $stats = [
            'companies' => Company::count(),
            'employees' => Employee::count(),
            'products'  => Product::count(),
        ];

        $latestCompanies = Company::latest()->take(5)->get();

        return view('admin.pages.home', compact('stats', 'latestCompanies'));
    }
}
