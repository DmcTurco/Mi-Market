<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bitácora (quién hizo qué) y borrado suave (nada se borra de verdad).
 */
class AuditAndSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company  = Company::first();
        $this->employee = Employee::first();
    }

    public function test_price_change_is_logged_with_before_and_after(): void
    {
        $this->actingAs($this->company, 'company');
        $product = $this->company->products()->create(['name' => 'Arroz', 'sale_price' => 4.50, 'stock' => 10]);

        $this->put("/company/products/{$product->id}", [
            'name' => 'Arroz', 'sale_price' => '4.20', 'stock' => '10', 'unit' => 'unidad', 'igv_affectation' => '10', 'active' => '1',
        ])->assertSessionHasNoErrors();

        $log = AuditLog::where('action', 'product.updated')->latest('id')->firstOrFail();
        $this->assertSame('Cambió el producto «Arroz»: Precio de venta: 4.50 → 4.20', $log->description);
        $this->assertSame(['sale_price' => ['4.50', '4.20']], $log->changes);
        $this->assertSame('company', $log->actor_type);
        $this->assertSame($this->company->id, (int) $log->company_id);
    }

    public function test_stock_correction_is_logged(): void
    {
        $this->actingAs($this->company, 'company');
        $product = $this->company->products()->create(['name' => 'Arroz', 'sale_price' => 4.50, 'stock' => 10]);

        $this->put("/company/products/{$product->id}", [
            'name' => 'Arroz', 'sale_price' => '4.50', 'stock' => '7', 'unit' => 'unidad', 'igv_affectation' => '10', 'active' => '1',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'stock.adjust', 'description' => 'Corrigió a mano el stock de «Arroz»: 10 → 7']);
        // El cambio de stock no se duplica como "producto actualizado"
        $this->assertDatabaseMissing('audit_logs', ['action' => 'product.updated']);
    }

    public function test_sale_discount_void_and_cash_close_are_logged_with_the_employee(): void
    {
        $product = $this->company->products()->create(['name' => 'Cerveza', 'sale_price' => 7, 'stock' => 10]);
        $this->actingAs($this->employee, 'employee');
        $this->post('/employee/cash/open', ['opening_amount' => 50]);

        $id = $this->postJson('/employee/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 6.5]],
            'voucher_type' => 'ticket', 'payment_method' => 'efectivo',
        ])->json('id');
        $this->post("/employee/sales/{$id}/void", ['reason' => 'Se arrepintió']);
        $this->post('/employee/cash/close', ['closing_amount' => 45]);

        $actions = AuditLog::where('actor_type', 'employee')->where('actor_id', $this->employee->id)->pluck('description', 'action');
        $this->assertSame('Abrió caja con S/ 50.00', $actions['cash.open']);
        $this->assertStringStartsWith('Vendió T001-00000001 por S/ 13.00', $actions['sale.created']);
        $this->assertStringStartsWith('Descontó S/ 1.00 en T001-00000001', $actions['sale.discount']);
        $this->assertSame('Anuló la venta T001-00000001 de S/ 13.00: Se arrepintió', $actions['sale.void']);
        $this->assertSame('Cerró caja: debía haber S/ 50.00, contó S/ 45.00 (faltan S/ 5.00)', $actions['cash.close']);
    }

    public function test_company_sees_only_its_own_log(): void
    {
        $other = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secreto123']);
        AuditLog::record('sale.created', 'Venta de la otra tienda', companyId: $other->id);
        AuditLog::record('sale.created', 'Venta de mi tienda', companyId: $this->company->id);

        $this->actingAs($this->company, 'company')
            ->get('/company/audit')->assertOk()
            ->assertSee('Venta de mi tienda')
            ->assertDontSee('Venta de la otra tienda');
    }

    public function test_deleting_is_soft_and_history_keeps_names(): void
    {
        $product = $this->company->products()->create(['name' => 'Cerveza', 'sale_price' => 7, 'stock' => 10]);
        $this->actingAs($this->employee, 'employee');
        $this->post('/employee/cash/open', ['opening_amount' => 0]);
        $id = $this->postJson('/employee/sales', ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'voucher_type' => 'ticket', 'payment_method' => 'yape'])->json('id');

        $this->actingAs($this->company, 'company');
        $this->delete("/company/products/{$product->id}");
        $this->delete("/company/employees/{$this->employee->id}");

        // No se borraron de la base: solo quedaron marcados
        $this->assertSoftDeleted($product);
        $this->assertSoftDeleted($this->employee);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.deleted']);

        // La venta sigue mostrando quién vendió, y la caja también
        $this->get("/company/sales/{$id}")->assertOk()->assertSee($this->employee->name);
        $this->get('/company/cash-registers')->assertOk()->assertSee($this->employee->name);
        $this->assertSame($this->employee->name, Sale::find($id)->employee->name);
    }

    public function test_email_of_a_deleted_employee_can_be_reused(): void
    {
        $this->actingAs($this->company, 'company');
        $email = $this->employee->email;
        $this->delete("/company/employees/{$this->employee->id}");

        $this->post('/company/employees', [
            'name' => 'Nuevo cajero', 'email' => $email, 'password' => 'secreto123', 'password_confirmation' => 'secreto123', 'active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Employee::where('email', $email)->count());          // el activo
        $this->assertSame(2, Employee::withTrashed()->where('email', $email)->count()); // y el eliminado
    }

    public function test_audit_failure_never_breaks_the_sale(): void
    {
        // Aunque la bitácora falle (p. ej. tabla bloqueada), la operación principal sigue
        \Illuminate\Support\Facades\Schema::rename('audit_logs', 'audit_logs_tmp');
        try {
            $this->actingAs($this->company, 'company');
            $this->post('/company/categories', ['name' => 'Bebidas', 'active' => '1'])->assertRedirect();
            $this->assertDatabaseHas('categories', ['name' => 'Bebidas']);
        } finally {
            \Illuminate\Support\Facades\Schema::rename('audit_logs_tmp', 'audit_logs');
        }
    }
}
