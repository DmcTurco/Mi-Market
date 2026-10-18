<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Corregir un ingreso de mercadería: el stock se ajusta solo con la diferencia.
 */
class PurchaseEditTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Product $beer;
    private Product $rice;
    private int $boxId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::first();
        $this->beer    = $this->company->products()->create(['name' => 'Cerveza', 'sale_price' => 6.80, 'purchase_price' => 5, 'stock' => 10]);
        $this->rice    = $this->company->products()->create(['name' => 'Arroz', 'sale_price' => 4.50, 'purchase_price' => 3.80, 'stock' => 5]);
        $this->boxId   = $this->beer->presentations()->create(['company_id' => $this->company->id, 'name' => 'Caja', 'units' => 12, 'price' => 72])->id;
    }

    private function register(string $prefix, array $extra = []): Purchase
    {
        // El error del usuario: 2 cajas con el costo de UNA botella
        $this->post("/{$prefix}/purchases", ['supplier' => 'Backus', 'items' => [
            ['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 5],
        ]] + $extra)->assertRedirect();

        return Purchase::latest('id')->firstOrFail();
    }

    public function test_the_form_shows_what_was_entered(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->register('company');

        // El formulario recibe "2 cajas a S/ 5", no "24 unidades"
        $this->get("/company/purchases/{$purchase->id}/edit")->assertOk()
            ->assertSee('Corregir mercadería')
            ->assertViewHas('items', fn ($items) => $items === [[
                'product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => '2', 'unit_cost' => '5.00',
            ]]);
        $this->get("/company/purchases/{$purchase->id}")->assertSee('2 caja')->assertSee('= 24 unidades');
    }

    public function test_fixing_the_cost_keeps_the_stock_and_fixes_the_cost(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->register('company');
        $this->assertEquals(0.42, (float) $this->beer->fresh()->purchase_price);

        $this->put("/company/purchases/{$purchase->id}", ['supplier' => 'Backus', 'items' => [
            ['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 60],
        ]])->assertRedirect(route('company.purchases.show', $purchase));

        $this->assertEquals(34, (float) $this->beer->fresh()->stock);           // no cambió: siguen siendo 24
        $this->assertEquals(5, (float) $this->beer->fresh()->purchase_price);   // 60 / 12
        $this->assertEquals(120, (float) $purchase->fresh()->total);
        $this->assertDatabaseMissing('stock_movements', ['reason' => 'purchase_edit']); // sin diferencia de stock
    }

    public function test_changing_quantities_and_products_moves_only_the_difference(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->register('company');

        // Era 2 cajas (24); en realidad llegó 1 caja (12) y además 10 de arroz
        $this->put("/company/purchases/{$purchase->id}", ['items' => [
            ['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 1, 'unit_cost' => 60],
            ['product_id' => $this->rice->id, 'quantity' => 10, 'unit_cost' => 3.5],
        ]])->assertRedirect();

        $this->assertEquals(22, (float) $this->beer->fresh()->stock);
        $this->assertEquals(15, (float) $this->rice->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $this->beer->id, 'reason' => 'purchase_edit', 'quantity' => -12]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $this->rice->id, 'reason' => 'purchase_edit', 'quantity' => 10]);
        $this->assertSame(2, $purchase->items()->count());
        $this->assertEquals(95, (float) $purchase->fresh()->total);
    }

    public function test_cannot_remove_merchandise_already_sold(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->register('company');
        $this->beer->update(['stock' => 15]); // se vendieron 19 de las 34

        // Quitar las 2 cajas (24) no alcanza: quedan 15
        $this->put("/company/purchases/{$purchase->id}", ['items' => [
            ['product_id' => $this->rice->id, 'quantity' => 1],
        ]])->assertSessionHas('error');

        $this->assertEquals(15, (float) $this->beer->fresh()->stock);
        $this->assertSame($this->beer->id, $purchase->items()->first()->product_id);
    }

    public function test_paid_from_open_register_updates_the_expense(): void
    {
        $employee = Employee::first();
        $this->actingAs($employee, 'employee');
        $this->post('/employee/cash/open', ['opening_amount' => 200]);
        $purchase = $this->register('employee', ['paid_from_cash' => '1']);
        $this->assertEquals(190, $employee->openCashRegister()->summary()['expected_cash']);

        $this->get("/employee/purchases/{$purchase->id}")->assertSee('Corregir cantidades o precios');
        $this->put("/employee/purchases/{$purchase->id}", ['items' => [
            ['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 60],
        ]])->assertRedirect();

        $this->assertEquals(80, $employee->openCashRegister()->summary()['expected_cash']); // 200 − 120
    }

    public function test_total_of_a_closed_register_cannot_change(): void
    {
        $employee = Employee::first();
        $this->actingAs($employee, 'employee');
        $this->post('/employee/cash/open', ['opening_amount' => 200]);
        $purchase = $this->register('employee', ['paid_from_cash' => '1']);
        $this->post('/employee/cash/close', ['closing_amount' => 190]);

        $this->actingAs($this->company, 'company');
        $this->put("/company/purchases/{$purchase->id}", ['items' => [
            ['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 60],
        ]])->assertSessionHas('error');
        $this->assertEquals(10, (float) $purchase->fresh()->total);

        // Con el mismo total sí se puede corregir (por ejemplo, el proveedor)
        $this->put("/company/purchases/{$purchase->id}", ['supplier' => 'Backus SAC', 'items' => [
            ['product_id' => $this->beer->id, 'presentation_id' => $this->boxId, 'quantity' => 2, 'unit_cost' => 5],
        ]])->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame('Backus SAC', $purchase->fresh()->supplier);
    }

    public function test_employee_cannot_edit_someone_elses_purchase(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->register('company');

        $this->actingAs(Employee::first(), 'employee');
        $this->get("/employee/purchases/{$purchase->id}/edit")->assertForbidden();
        $this->put("/employee/purchases/{$purchase->id}", ['items' => [['product_id' => $this->beer->id, 'quantity' => 1]]])->assertForbidden();
    }

    public function test_voided_purchase_cannot_be_edited(): void
    {
        $this->actingAs($this->company, 'company');
        $purchase = $this->register('company');
        $this->post("/company/purchases/{$purchase->id}/void", ['reason' => 'No llegó']);

        $this->get("/company/purchases/{$purchase->id}/edit")->assertNotFound();
        $this->put("/company/purchases/{$purchase->id}", ['items' => [['product_id' => $this->beer->id, 'quantity' => 1]]])
            ->assertSessionHas('error', 'Este ingreso está anulado: ya no se puede editar.');
    }
}
