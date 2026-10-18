<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cada panel solo se abre con la sesión de su propio guard.
 */
class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/admin/home', '/company/home', '/employee/home'] as $url) {
            $this->get($url)->assertRedirectContains('login');
        }
    }

    public function test_each_guard_opens_only_its_own_panel(): void
    {
        $this->actingAs(Admin::first(), 'admin')
            ->get('/admin/home')->assertOk()->assertSee('Panel de Administración');
        $this->get('/company/home')->assertRedirectContains('login');

        $this->actingAs(Company::first(), 'company')
            ->get('/company/home')->assertOk()->assertSee(Company::first()->name);

        $this->actingAs(Employee::first(), 'employee')
            ->get('/employee/home')->assertOk()->assertSee('Hola, ' . Employee::first()->name);
    }

    public function test_admin_registers_a_company_that_can_have_employees(): void
    {
        $this->actingAs(Admin::first(), 'admin')
            ->post('/admin/companies', [
                'name'                  => 'Bodega Don Pepe',
                'email'                 => 'pepe@example.com',
                'password'              => 'secreto123',
                'password_confirmation' => 'secreto123',
            ])
            ->assertRedirect(route('admin.companies.index'));

        $company = Company::where('email', 'pepe@example.com')->firstOrFail();

        $this->actingAs($company, 'company')
            ->post('/company/employees', [
                'name'                  => 'Ana Cajera',
                'email'                 => 'ana@example.com',
                'password'              => 'secreto123',
                'password_confirmation' => 'secreto123',
                'active'                => '1',
            ])
            ->assertRedirect(route('company.employees.index'));

        $this->assertDatabaseHas('employees', ['email' => 'ana@example.com', 'company_id' => $company->id, 'active' => true]);
    }

    public function test_a_company_cannot_touch_another_companys_employees(): void
    {
        $other    = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secreto123']);
        $employee = $other->employees()->create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secreto123']);

        $this->actingAs(Company::first(), 'company');

        $this->get("/company/employees/{$employee->id}/edit")->assertNotFound();
        $this->delete("/company/employees/{$employee->id}")->assertNotFound();
        $this->assertModelExists($employee);
    }
}
