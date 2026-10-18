<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\EmployeeRequest;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Empleados de la empresa que inició sesión.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->buscar);

        $employees = $this->company()->employees()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . mb_strtolower($search) . '%';
                $query->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(name) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('company.pages.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('company.pages.employees.form');
    }

    public function store(EmployeeRequest $request)
    {
        $employee = $this->company()->employees()->create(
            $request->safe()->only(['name', 'email', 'password']) + ['active' => $request->boolean('active')]
        );

        return redirect()->route('company.employees.index')
            ->with('success', "Empleado «{$employee->name}» registrado. Entra con {$employee->email} en /employee/login.");
    }

    public function edit(Employee $employee)
    {
        $this->authorizeEmployee($employee);

        return view('company.pages.employees.form', compact('employee'));
    }

    /**
     * Actualiza al empleado. La contraseña solo cambia si se escribe una nueva.
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        $this->authorizeEmployee($employee);

        $data = $request->safe()->only(['name', 'email']) + ['active' => $request->boolean('active')];
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $employee->update($data);

        return redirect()->route('company.employees.index')
            ->with('success', "Empleado «{$employee->name}» actualizado.");
    }

    public function destroy(Employee $employee)
    {
        $this->authorizeEmployee($employee);

        $employee->delete();

        return redirect()->route('company.employees.index')
            ->with('success', "Empleado «{$employee->name}» eliminado.");
    }

    private function company(): Company
    {
        return auth()->guard('company')->user();
    }

    /**
     * Una empresa solo puede ver o tocar a sus propios empleados.
     */
    private function authorizeEmployee(Employee $employee): void
    {
        abort_unless((int) $employee->company_id === (int) $this->company()->id, 404);
    }
}
