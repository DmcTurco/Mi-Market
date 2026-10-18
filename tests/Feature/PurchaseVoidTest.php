<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Corregir un ingreso de mercadería registrado por error.
 */
class PurchaseVoidTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Product $beer;
    private int $boxId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::first();
        $this->beer    = $this->company->products()->create(['name' => 'Cerveza', 'sale_price' => 6.80, 'purchase_price' => 5, 'stock' => 10]);
        $this->boxId   = $this->beer->presentations()->create(['company_id' => $this->company->id, 'name' => 'Caja', 'units' => 12, 'price' => 72])->id;
    }

    /** El error del usuario: 2 cajas con el costo de UNA botella (S/ 5) */
    private function wrongPurchase(array $extra = []): Purchase
    {
        $this->post($extra['url'] ?? '/company/purchases', [
            'items' => [['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 5]],
        ] + ($extra['data'] ?? []))->assertRedirect();

        return Purchase::latest('id')->firstOrFail();
    }

    public function test_company_voids_a_wrong_purchase_and_registers_it_again(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->wrongPurchase();

        // Quedó mal: 34 botellas a S/ 0.42 cada una
        $this->assertEquals(34, (float) $this->beer->fresh()->stock);
        $this->assertEquals(0.42, (float) $this->beer->fresh()->purchase_price);

        $this->post("/company/purchases/{$purchase->id}/void", ['reason' => 'Puse el costo de una botella'])->assertRedirect();

        $this->assertTrue($purchase->fresh()->isVoided());
        $this->assertEquals(10, (float) $this->beer->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $this->beer->id, 'reason' => 'purchase_void', 'quantity' => -24]);

        // Se registra bien: 2 cajas a S/ 60
        $this->post('/company/purchases', ['items' => [['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 60]]]);
        $this->assertEquals(34, (float) $this->beer->fresh()->stock);
        $this->assertEquals(5, (float) $this->beer->fresh()->purchase_price);
    }

    public function test_cannot_void_twice(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->wrongPurchase();

        $this->post("/company/purchases/{$purchase->id}/void", ['reason' => 'Error']);
        $this->post("/company/purchases/{$purchase->id}/void", ['reason' => 'Error'])->assertSessionHas('error', 'Este ingreso ya estaba anulado.');
        $this->assertEquals(10, (float) $this->beer->fresh()->stock);
    }

    public function test_cannot_void_if_the_merchandise_was_already_sold(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->wrongPurchase();
        $this->beer->update(['stock' => 20]); // se vendieron 14 de las 34

        $this->post("/company/purchases/{$purchase->id}/void", ['reason' => 'Error'])->assertSessionHas('error');
        $this->assertFalse($purchase->fresh()->isVoided());
        $this->assertEquals(20, (float) $this->beer->fresh()->stock);
    }

    public function test_employee_void_returns_the_money_to_the_open_register(): void
    {
        $employee = Employee::first();
        $this->actingAs($employee, 'employee');
        $this->post('/employee/cash/open', ['opening_amount' => 100]);

        $purchase = $this->wrongPurchase(['url' => '/employee/purchases', 'data' => ['paid_from_cash' => '1']]);
        $this->assertEquals(90, $employee->openCashRegister()->summary()['expected_cash']); // 100 − 2×5

        $this->get("/employee/purchases/{$purchase->id}")->assertOk()->assertSee('Anular este ingreso');
        $this->post("/employee/purchases/{$purchase->id}/void", ['reason' => 'Me equivoqué'])->assertRedirect();

        $this->assertEquals(100, $employee->openCashRegister()->summary()['expected_cash']);
        $this->assertSame(0, CashMovement::count());
        $this->assertEquals(10, (float) $this->beer->fresh()->stock);
    }

    public function test_employee_cannot_void_purchases_from_other_days_or_people(): void
    {
        $this->actingAs($this->company, 'company');
        $byCompany = $this->wrongPurchase();

        $this->actingAs(Employee::first(), 'employee');
        $this->get("/employee/purchases/{$byCompany->id}")->assertOk()->assertDontSee('Anular este ingreso');
        $this->post("/employee/purchases/{$byCompany->id}/void", ['reason' => 'x'])->assertForbidden();
    }
}
