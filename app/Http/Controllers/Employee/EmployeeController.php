<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    /**
     * Inicio del empleado: si no abrió caja, solo ve "Abrir caja"; si ya la abrió, los botones grandes del día.
     */
    public function index()
    {
        /** @var \App\Models\Employee $employee */
        $employee = auth()->guard('employee')->user();
        $employee->load('company');
        $register = $employee->openCashRegister();

        return view('employee.pages.home', [
            'employee' => $employee,
            'register' => $register,
            'summary'  => $register?->summary(),
            'lowStock' => $employee->company->products()->where('active', true)->lowStock()->count(),
        ]);
    }
}
