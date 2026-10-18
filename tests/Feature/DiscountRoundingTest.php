<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rebajas de precio, descuento a la venta (con tope) y redondeo del efectivo a S/ 0.10.
 */
class DiscountRoundingTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Employee $employee;
    private Product $beer;
    private Product $potato;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company  = Company::first();
        $this->employee = Employee::first();
        $this->beer     = $this->company->products()->create(['name' => 'Cerveza', 'sale_price' => 7.00, 'stock' => 100]);
        $this->potato   = $this->company->products()->create(['name' => 'Papa amarilla', 'unit' => 'kg', 'sale_price' => 3.50, 'stock' => 50, 'igv_affectation' => '20']);

        $this->actingAs($this->employee, 'employee');
        $this->post('/employee/cash/open', ['opening_amount' => 0]);
    }

    private function sell(array $payload)
    {
        return $this->postJson('/employee/sales', $payload + ['voucher_type' => 'ticket', 'payment_method' => 'yape']);
    }

    // ── Rebaja de precio ─────────────────────────────────────

    public function test_cashier_lowers_the_price_of_a_line(): void
    {
        // "Te dejo cada cerveza a S/ 6.50": 4 × 6.50 = 26.00 (normal 28.00)
        $this->sell(['items' => [['product_id' => $this->beer->id, 'quantity' => 4, 'price' => 6.50]]])
            ->assertOk()->assertJson(['total' => 26.0]);

        $sale = Sale::first();
        $this->assertEquals(28, (float) $sale->gross_total);
        $this->assertEquals(2, (float) $sale->discount_amount);

        $item = $sale->items->first();
        $this->assertEquals(7, (float) $item->list_price);
        $this->assertEquals(6.5, (float) $item->unit_price);
        $this->assertEquals(2, (float) $item->discount);
    }

    public function test_price_cannot_go_up(): void
    {
        $this->sell(['items' => [['product_id' => $this->beer->id, 'quantity' => 1, 'price' => 9]]])
            ->assertStatus(422)->assertJsonFragment(['message' => 'No se puede cobrar «Cerveza» más caro que su precio (S/ 7.00).']);
    }

    // ── Descuento a la venta ─────────────────────────────────

    public function test_amount_discount_is_spread_across_lines(): void
    {
        // 2 cervezas (14) + 2 kg papa (7) = 21; descuento S/ 1 → 20
        $this->sell([
            'items'           => [['product_id' => $this->beer->id, 'quantity' => 2], ['product_id' => $this->potato->id, 'quantity' => 2]],
            'discount_amount' => 1,
        ])->assertOk()->assertJson(['total' => 20.0]);

        $sale = Sale::first();
        $this->assertEquals(1, (float) $sale->discount_amount);
        $this->assertEquals(1, round((float) $sale->items->sum('discount'), 2));
        // La parte gravada (cerveza) baja su base e IGV; la papa es exonerada
        $this->assertEquals(round((float) $sale->taxable_amount + (float) $sale->igv + (float) $sale->exonerated_amount, 2), 20.0);
    }

    public function test_percent_discount(): void
    {
        // 10 cervezas = 70; 10% = 7 → 63
        $this->sell(['items' => [['product_id' => $this->beer->id, 'quantity' => 10]], 'discount_percent' => 10])
            ->assertOk()->assertJson(['total' => 63.0]);
    }

    public function test_discount_above_the_limit_is_rejected(): void
    {
        $this->company->update(['max_discount_percent' => 10]);

        // Rebaja de 7 → 5 en 2 cervezas (S/ 4) + descuento S/ 1 = 5 de 14 → 35% > 10%
        $this->sell([
            'items'           => [['product_id' => $this->beer->id, 'quantity' => 2, 'price' => 5]],
            'discount_amount' => 1,
        ])->assertStatus(422)->assertJsonFragment(['message' => 'El descuento máximo es 10% de la venta (S/ 1.40). Estás descontando S/ 5.00.']);

        $this->assertSame(0, Sale::count());
    }

    public function test_discounts_can_be_turned_off(): void
    {
        $this->company->update(['allow_discounts' => false]);

        $this->sell(['items' => [['product_id' => $this->beer->id, 'quantity' => 1, 'price' => 6]]])->assertStatus(422);
        $this->sell(['items' => [['product_id' => $this->beer->id, 'quantity' => 1]], 'discount_amount' => 1])->assertStatus(422);
        // Sin descuento sí vende
        $this->sell(['items' => [['product_id' => $this->beer->id, 'quantity' => 1]]])->assertOk();
    }

    // ── Redondeo del efectivo ────────────────────────────────

    public function test_cash_is_rounded_down_in_favor_of_customer(): void
    {
        // 1.22 kg de papa × 3.50 = 4.27 → se cobra 4.20; paga con 5 → vuelto 0.80
        $this->sell(['items' => [['product_id' => $this->potato->id, 'quantity' => 1.22]], 'payment_method' => 'efectivo', 'amount_received' => 5])
            ->assertOk()->assertJson(['total' => 4.27, 'charged' => 4.2, 'change' => 0.8]);

        $sale = Sale::first();
        $this->assertEquals(0.07, (float) $sale->rounding_amount);
        // La caja espera lo que realmente entró
        $this->assertEquals(4.2, $this->employee->openCashRegister()->summary()['expected_cash']);
    }

    public function test_cash_rounding_in_favor_of_business(): void
    {
        $this->company->update(['rounding_favor' => 'business']);

        $this->sell(['items' => [['product_id' => $this->potato->id, 'quantity' => 1.22]], 'payment_method' => 'efectivo'])
            ->assertOk()->assertJson(['total' => 4.27, 'charged' => 4.3]);
        $this->assertEquals(-0.03, (float) Sale::first()->rounding_amount);
    }

    public function test_paying_the_rounded_amount_is_enough(): void
    {
        $this->sell(['items' => [['product_id' => $this->potato->id, 'quantity' => 1.22]], 'payment_method' => 'efectivo', 'amount_received' => 4.20])
            ->assertOk()->assertJson(['change' => 0.0]);
    }

    public function test_yape_and_credit_are_not_rounded(): void
    {
        $this->sell(['items' => [['product_id' => $this->potato->id, 'quantity' => 1.22]]])->assertOk()->assertJson(['charged' => 4.27]);
        $this->sell(['items' => [['product_id' => $this->potato->id, 'quantity' => 1.22]], 'payment_method' => 'fiado', 'customer_name' => 'Rosa'])->assertOk();

        $this->assertEquals(4.27, (float) Sale::where('payment_method', 'fiado')->first()->credit_balance);
        $this->assertSame(0, Sale::where('rounding_amount', '!=', 0)->count());
    }

    public function test_rounding_can_be_turned_off(): void
    {
        $this->company->update(['cash_rounding' => false]);

        $this->sell(['items' => [['product_id' => $this->potato->id, 'quantity' => 1.22]], 'payment_method' => 'efectivo'])
            ->assertOk()->assertJson(['charged' => 4.27]);
    }

    // ── Configuración ────────────────────────────────────────

    public function test_company_changes_its_settings(): void
    {
        $this->actingAs($this->company, 'company');
        $this->get('/company/settings')->assertOk();

        $this->put('/company/settings', ['allow_discounts' => '0', 'max_discount_percent' => '15', 'cash_rounding' => '1', 'rounding_favor' => 'business'])
            ->assertRedirect(route('company.settings.edit'));

        $this->company->refresh();
        $this->assertFalse($this->company->allow_discounts);
        $this->assertSame(15, $this->company->max_discount_percent);
        $this->assertSame('business', $this->company->rounding_favor);
    }
}
