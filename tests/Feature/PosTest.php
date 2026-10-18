<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flujo del cajero: abrir caja, vender, anular, fiado, gastos, mercadería y cierre.
 */
class PosTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;
    private Product $rice;
    private Product $cheese;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::first();
        $this->employee = Employee::first();

        $this->rice   = $company->products()->create(['name' => 'Arroz 1kg', 'barcode' => '7750001', 'sale_price' => 4.50, 'purchase_price' => 3.80, 'stock' => 10]);
        $this->cheese = $company->products()->create(['name' => 'Queso fresco', 'unit' => 'kg', 'sale_price' => 20, 'stock' => 5, 'igv_affectation' => '20']);

        $this->actingAs($this->employee, 'employee');
    }

    private function openRegister(float $amount = 50): void
    {
        $this->post('/employee/cash/open', ['opening_amount' => $amount])->assertRedirect(route('employee.home'));
    }

    private function sell(array $payload)
    {
        return $this->postJson('/employee/sales', $payload + ['voucher_type' => 'ticket', 'payment_method' => 'efectivo']);
    }

    public function test_cannot_sell_without_an_open_register(): void
    {
        $this->get('/employee/pos')->assertRedirect(route('employee.cash.open'));
        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]]])->assertStatus(422);
    }

    public function test_cash_sale_discounts_stock_and_gives_change(): void
    {
        $this->openRegister();

        $response = $this->sell([
            'items'           => [['product_id' => $this->rice->id, 'quantity' => 2], ['product_id' => $this->cheese->id, 'quantity' => 0.5]],
            'amount_received' => 20,
        ])->assertOk();

        // 2 × 4.50 + 0.5 kg × 20 = 19.00 → vuelto 1.00
        $response->assertJson(['total' => 19.0, 'change' => 1.0, 'number' => 'T001-00000001']);
        $this->assertEquals(8, (float) $this->rice->fresh()->stock);
        $this->assertEquals(4.5, (float) $this->cheese->fresh()->stock);

        // El arroz es gravado: base 9 / 1.18 = 7.63, IGV 1.37. El queso es exonerado.
        $sale = Sale::first();
        $this->assertEquals(7.63, (float) $sale->taxable_amount);
        $this->assertEquals(1.37, (float) $sale->igv);
        $this->assertEquals(10.0, (float) $sale->exonerated_amount);
    }

    public function test_a_manipulated_price_beyond_the_discount_limit_is_rejected(): void
    {
        $this->openRegister();

        // El navegador manda S/ 0.01: es una "rebaja" del 99%, muy por encima del tope (20%)
        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1, 'price' => 0.01]]])
            ->assertStatus(422);
        $this->assertSame(0, Sale::count());

        // Sin precio, se cobra el de la base de datos
        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]]])
            ->assertOk()->assertJson(['total' => 4.5]);
    }

    public function test_not_enough_stock_is_rejected_and_nothing_is_saved(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 11]]])
            ->assertStatus(422)->assertJsonFragment(['message' => 'No alcanza el stock de «Arroz 1kg»: quedan 10 y se necesitan 11. Si llegó mercadería, regístrala primero.']);

        $this->assertSame(0, Sale::count());
        $this->assertEquals(10, (float) $this->rice->fresh()->stock);
    }

    public function test_units_cannot_be_sold_in_fractions(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1.5]]])->assertStatus(422);
    }

    public function test_paying_less_than_total_is_rejected(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 2]], 'amount_received' => 5])
            ->assertStatus(422);
    }

    public function test_cannot_sell_another_companys_product(): void
    {
        $other   = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secreto123']);
        $foreign = $other->products()->create(['name' => 'Ajeno', 'sale_price' => 1, 'stock' => 100]);
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $foreign->id, 'quantity' => 1]]])->assertStatus(422);
        $this->assertEquals(100, (float) $foreign->fresh()->stock);
    }

    public function test_void_returns_stock_and_leaves_the_cash_count(): void
    {
        $this->openRegister(50);
        $id = $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 2]]])->json('id');

        $this->post("/employee/sales/{$id}/void", ['reason' => 'Se equivocó de producto'])->assertRedirect();

        $this->assertEquals(10, (float) $this->rice->fresh()->stock);
        $this->assertTrue(Sale::find($id)->isVoided());
        $this->assertEquals(50, $this->employee->openCashRegister()->summary()['expected_cash']);
    }

    public function test_credit_sale_and_payments_apply_oldest_first(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 2]], 'payment_method' => 'fiado', 'customer_name' => 'Doña Rosa', 'customer_phone' => '999888777'])->assertOk();
        $clientId = Sale::first()->client_id;
        $this->assertNotNull($clientId);

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]], 'payment_method' => 'fiado', 'client_id' => $clientId])->assertOk();

        // Debe 9 + 4.50 = 13.50; paga 10 → la primera queda pagada y la segunda debe 3.50
        $this->post("/employee/clients/{$clientId}/pay", ['amount' => 10, 'payment_method' => 'efectivo'])->assertRedirect();

        $sales = Sale::orderBy('id')->get();
        $this->assertEquals(0, (float) $sales[0]->credit_balance);
        $this->assertEquals(3.5, (float) $sales[1]->credit_balance);

        // Pagar más de lo que debe no se permite
        $this->post("/employee/clients/{$clientId}/pay", ['amount' => 5, 'payment_method' => 'efectivo'])->assertSessionHas('error');
    }

    public function test_credit_requires_a_client(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]], 'payment_method' => 'fiado'])->assertStatus(422);
    }

    public function test_purchase_paid_from_register_raises_stock_and_is_an_expense(): void
    {
        $this->openRegister(100);

        $this->post('/employee/purchases', [
            'supplier'       => 'Distribuidora Lima',
            'paid_from_cash' => '1',
            'items'          => [['product_id' => $this->rice->id, 'quantity' => 12, 'unit_cost' => 3.5]],
        ])->assertRedirect();

        $this->assertEquals(22, (float) $this->rice->fresh()->stock);
        $this->assertEquals(3.5, (float) $this->rice->fresh()->purchase_price);
        $this->assertDatabaseHas('cash_movements', ['type' => CashMovement::EXPENSE, 'amount' => 42]);
    }

    public function test_closing_the_register_computes_the_difference(): void
    {
        $this->openRegister(50);

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 2]], 'amount_received' => 10])->assertOk(); // +9 efectivo
        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]], 'payment_method' => 'yape'])->assertOk(); // no es efectivo
        $this->post('/employee/cash/movements', ['type' => 'expense', 'concept' => 'Bolsas', 'amount' => 5])->assertRedirect();
        $this->post('/employee/cash/movements', ['type' => 'income', 'concept' => 'Sencillo', 'amount' => 20])->assertRedirect();

        // 50 + 9 − 5 + 20 = 74 esperado; cuenta 70 → faltan 4
        $register = $this->employee->openCashRegister();
        $this->assertEquals(74, $register->summary()['expected_cash']);

        $this->post('/employee/cash/close', ['closing_amount' => 70])->assertRedirect(route('employee.home'));

        $register->refresh();
        $this->assertFalse($register->isOpen());
        $this->assertEquals(-4, (float) $register->difference);
    }

    public function test_voucher_numbers_are_consecutive(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]]])->assertJson(['number' => 'T001-00000001']);
        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]]])->assertJson(['number' => 'T001-00000002']);
    }

    public function test_boleta_is_not_available_until_sunat_is_configured(): void
    {
        $this->openRegister();

        $this->sell(['items' => [['product_id' => $this->rice->id, 'quantity' => 1]], 'voucher_type' => 'boleta'])
            ->assertStatus(422)->assertJsonValidationErrors('voucher_type');
    }
}
