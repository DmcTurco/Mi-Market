<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vender por unidad, media caja o caja (el stock se cuenta en unidades) y por peso.
 */
class PresentationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Product $beer;
    private ProductPresentation $box;
    private ProductPresentation $halfBox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::first();
        $this->beer    = $this->company->products()->create(['name' => 'Cerveza Cristal', 'barcode' => '111', 'sale_price' => 6.80, 'stock' => 30]);
        $this->box     = $this->beer->presentations()->create(['company_id' => $this->company->id, 'name' => 'Caja', 'units' => 12, 'price' => 72, 'barcode' => '222']);
        $this->halfBox = $this->beer->presentations()->create(['company_id' => $this->company->id, 'name' => 'Media caja', 'units' => 6, 'price' => 38]);
    }

    private function sellAsEmployee(array $items)
    {
        $this->actingAs(Employee::first(), 'employee');
        if (!Employee::first()->openCashRegister()) {
            $this->post('/employee/cash/open', ['opening_amount' => 0]);
        }

        return $this->postJson('/employee/sales', ['items' => $items, 'voucher_type' => 'ticket', 'payment_method' => 'efectivo']);
    }

    public function test_selling_boxes_uses_box_price_and_discounts_units(): void
    {
        // 1 caja (12) + 1 media caja (6) + 3 sueltas = 21 unidades; 72 + 38 + 3×6.80 = 130.40
        $response = $this->sellAsEmployee([
            ['product_id' => $this->beer->id, 'presentation_id' => $this->box->id, 'quantity' => 1],
            ['product_id' => $this->beer->id, 'presentation_id' => $this->halfBox->id, 'quantity' => 1],
            ['product_id' => $this->beer->id, 'quantity' => 3],
        ])->assertOk();

        $response->assertJson(['total' => 130.4]);
        $this->assertEquals(9, (float) $this->beer->fresh()->stock);

        $item = Sale::first()->items()->where('presentation_id', $this->box->id)->first();
        $this->assertSame('Cerveza Cristal (Caja x12)', $item->product_name);
        $this->assertEquals(12, (float) $item->units_per_item);
    }

    public function test_not_enough_units_for_boxes_is_rejected(): void
    {
        // 3 cajas = 36 unidades, solo hay 30
        $this->sellAsEmployee([['product_id' => $this->beer->id, 'presentation_id' => $this->box->id, 'quantity' => 3]])
            ->assertStatus(422)->assertJsonFragment(['message' => 'No alcanza el stock de «Cerveza Cristal»: quedan 30 y se necesitan 36. Si llegó mercadería, regístrala primero.']);
        $this->assertEquals(30, (float) $this->beer->fresh()->stock);
    }

    public function test_boxes_cannot_be_sold_in_fractions(): void
    {
        $this->sellAsEmployee([['product_id' => $this->beer->id, 'presentation_id' => $this->box->id, 'quantity' => 0.5]])->assertStatus(422);
    }

    public function test_presentation_of_another_product_is_rejected(): void
    {
        $water = $this->company->products()->create(['name' => 'Agua', 'sale_price' => 1.5, 'stock' => 100]);

        $this->sellAsEmployee([['product_id' => $water->id, 'presentation_id' => $this->box->id, 'quantity' => 1]])->assertStatus(422);
        $this->assertEquals(100, (float) $water->fresh()->stock);
    }

    public function test_voiding_a_box_sale_returns_all_units(): void
    {
        $id = $this->sellAsEmployee([['product_id' => $this->beer->id, 'presentation_id' => $this->box->id, 'quantity' => 2]])->json('id');
        $this->assertEquals(6, (float) $this->beer->fresh()->stock);

        $this->post("/employee/sales/{$id}/void", ['reason' => 'Prueba'])->assertRedirect();
        $this->assertEquals(30, (float) $this->beer->fresh()->stock);
    }

    public function test_weighed_product_sells_any_fraction(): void
    {
        $rice = $this->company->products()->create(['name' => 'Arroz a granel', 'unit' => 'kg', 'sale_price' => 4.20, 'stock' => 10]);

        // ½ kilo y lo que marcó la balanza (0.350 kg)
        $this->sellAsEmployee([['product_id' => $rice->id, 'quantity' => 0.5], ['product_id' => $rice->id, 'quantity' => 0.35]])
            ->assertOk()->assertJson(['total' => 3.57]); // 0.85 kg × 4.20

        $this->assertEquals(9.15, (float) $rice->fresh()->stock);
    }

    public function test_merchandise_arrives_by_boxes(): void
    {
        $this->actingAs($this->company, 'company');

        // 2 cajas a S/ 60 cada una = 24 unidades a S/ 5.00
        $this->post('/company/purchases', [
            'items' => [['product_id' => $this->beer->id, 'presentation_id' => $this->box->id, 'quantity' => 2, 'unit_cost' => 60]],
        ])->assertRedirect();

        $this->assertEquals(54, (float) $this->beer->fresh()->stock);
        $this->assertEquals(5, (float) $this->beer->fresh()->purchase_price);
        $this->assertDatabaseHas('purchases', ['total' => 120]);
    }

    public function test_product_form_saves_updates_and_removes_presentations(): void
    {
        $this->actingAs($this->company, 'company');
        $base = ['sale_price' => '2.50', 'stock' => '0', 'unit' => 'unidad', 'igv_affectation' => '10', 'active' => '1'];

        $this->post('/company/products', $base + ['name' => 'Inca Kola 500ml', 'presentations' => [
            ['name' => 'Paquete', 'units' => '6', 'price' => '13,50', 'barcode' => '999'],
            ['name' => '', 'units' => '', 'price' => ''], // fila vacía: se ignora
        ]])->assertSessionHasNoErrors();

        $soda = Product::where('name', 'Inca Kola 500ml')->firstOrFail();
        $pack = $soda->presentations()->firstOrFail();
        $this->assertEquals(13.5, (float) $pack->price);

        // Editar: cambia el precio del paquete y agrega la caja
        $this->put("/company/products/{$soda->id}", $base + ['name' => 'Inca Kola 500ml', 'presentations' => [
            ['id' => $pack->id, 'name' => 'Paquete', 'units' => '6', 'price' => '14', 'barcode' => '999'],
            ['name' => 'Caja', 'units' => '24', 'price' => '50'],
        ]])->assertSessionHasNoErrors();
        $this->assertEquals(14, (float) $pack->fresh()->price);
        $this->assertSame(2, $soda->presentations()->count());

        // Quitar todas
        $this->put("/company/products/{$soda->id}", $base + ['name' => 'Inca Kola 500ml'])->assertSessionHasNoErrors();
        $this->assertSame(0, $soda->presentations()->count());
    }

    public function test_barcode_cannot_repeat_between_products_and_boxes(): void
    {
        $this->actingAs($this->company, 'company');
        $base = ['name' => 'Otro', 'sale_price' => '1', 'unit' => 'unidad', 'igv_affectation' => '10'];

        // '222' es el código de la caja de cerveza
        $this->post('/company/products', $base + ['barcode' => '222'])->assertSessionHasErrors('barcode');
        // '111' es el de la botella, usado en una presentación
        $this->post('/company/products', $base + ['presentations' => [['name' => 'Caja', 'units' => '10', 'price' => '9', 'barcode' => '111']]])->assertSessionHasErrors('barcode');
        // La caja no puede traer 1 sola unidad
        $this->post('/company/products', $base + ['presentations' => [['name' => 'Caja', 'units' => '1', 'price' => '9']]])->assertSessionHasErrors('presentations.0.units');
    }
}
