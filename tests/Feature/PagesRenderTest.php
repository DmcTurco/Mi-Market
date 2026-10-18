<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Abre cada pantalla con datos de verdad (venta, fiado, mercadería, caja cerrada) para detectar vistas rotas.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_employee_and_company_page_renders(): void
    {
        $company  = Company::first();
        $employee = Employee::first();
        $category = $company->categories()->create(['name' => 'Abarrotes']);
        $product  = $company->products()->create(['name' => 'Arroz 1kg', 'category_id' => $category->id, 'sale_price' => 4.5, 'stock' => 10, 'min_stock' => 20]);

        // Sin caja: el inicio invita a abrirla
        $this->actingAs($employee, 'employee');
        $this->get('/employee/home')->assertOk()->assertSee('abre tu caja');
        $this->get('/employee/cash/open')->assertOk();

        // Turno completo
        $this->post('/employee/cash/open', ['opening_amount' => 50]);
        $this->postJson('/employee/sales', ['items' => [['product_id' => $product->id, 'quantity' => 2]], 'voucher_type' => 'ticket', 'payment_method' => 'efectivo', 'amount_received' => 10])->assertOk();
        $this->postJson('/employee/sales', ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'voucher_type' => 'ticket', 'payment_method' => 'fiado', 'customer_name' => 'Doña Rosa'])->assertOk();
        $this->post('/employee/purchases', ['items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 3]], 'paid_from_cash' => '1']);
        $this->post('/employee/cash/movements', ['type' => 'expense', 'concept' => 'Bolsas', 'amount' => 2]);

        $sale   = Sale::first();
        $client = $company->clients()->first();
        $purchase = $company->purchases()->first();

        foreach ([
            '/employee/home', '/employee/pos', '/employee/sales', "/employee/sales/{$sale->id}", "/employee/sales/{$sale->id}/ticket",
            '/employee/clients', "/employee/clients/{$client->id}", '/employee/cash/movements', '/employee/cash/close',
            '/employee/purchases/create', "/employee/purchases/{$purchase->id}", '/employee/products', '/employee/products?filtro=pocos',
        ] as $url) {
            $this->get($url)->assertOk();
        }
        $this->getJson('/employee/clients/search?q=rosa')->assertOk()->assertJsonFragment(['name' => 'Doña Rosa', 'debt' => 4.5]);

        $this->post('/employee/cash/close', ['closing_amount' => 45]);
        $register = $company->cashRegisters()->first();

        // Panel de la empresa
        $this->actingAs($company, 'company');
        foreach ([
            '/company/home', '/company/products', '/company/products?filtro=pocos', '/company/products/create', "/company/products/{$product->id}/edit",
            "/company/products/{$product->id}/history", '/company/purchases', '/company/purchases/create', "/company/purchases/{$purchase->id}",
            '/company/sales', "/company/sales/{$sale->id}", "/company/sales/{$sale->id}/ticket", '/company/cash-registers',
            "/company/cash-registers/{$register->id}", '/company/clients', '/company/clients?filtro=todos', "/company/clients/{$client->id}",
            '/company/categories', '/company/employees',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_company_creates_product_with_initial_stock_in_history(): void
    {
        $company = Company::first();
        $this->actingAs($company, 'company');

        $this->post('/company/products', ['name' => 'Leche Gloria', 'sale_price' => '4,20', 'stock' => '24', 'unit' => 'unidad', 'igv_affectation' => '10'])
            ->assertRedirect(route('company.products.index'));

        $product = $company->products()->firstOrFail();
        $this->assertEquals(4.2, (float) $product->sale_price);
        $this->assertEquals(24, (float) $product->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'reason' => 'adjustment', 'notes' => 'Stock inicial']);

        // Corregir el stock desde la edición deja la diferencia en el historial
        $this->put("/company/products/{$product->id}", ['name' => 'Leche Gloria', 'sale_price' => '4.20', 'stock' => '20', 'unit' => 'unidad', 'igv_affectation' => '10', 'active' => '1'])
            ->assertRedirect();
        $this->assertEquals(20, (float) $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'quantity' => -4, 'notes' => 'Corrección manual']);
    }
}
