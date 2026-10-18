<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Sale;

class CompanyController extends Controller
{
    /**
     * Inicio de la empresa: lo vendido hoy, cajas abiertas, productos por acabarse y fiados.
     */
    public function index()
    {
        /** @var \App\Models\Company $company */
        $company = auth()->guard('company')->user();

        $today = $company->sales()->where('status', Sale::ACTIVE)->whereDate('created_at', today());

        return view('company.pages.home', [
            'company'      => $company,
            'salesToday'   => (float) (clone $today)->sum('total'),
            'countToday'   => (clone $today)->count(),
            'openRegisters'=> $company->cashRegisters()->open()->with('employee')->get(),
            'lowStock'     => $company->products()->where('active', true)->lowStock()->orderBy('name')->take(8)->get(),
            'debtTotal'    => (float) $company->sales()->where('status', Sale::ACTIVE)->sum('credit_balance'),
            'hasProducts'  => $company->products()->exists(),
            'hasEmployees' => $company->employees()->exists(),
        ]);
    }
}
