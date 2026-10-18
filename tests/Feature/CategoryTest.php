<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::first();
        $this->actingAs($this->company, 'company');
    }

    public function test_company_creates_updates_and_deletes_its_categories(): void
    {
        $this->post('/company/categories', ['name' => 'Bebidas', 'active' => '1'])
            ->assertRedirect(route('company.categories.index'));

        $category = $this->company->categories()->firstOrFail();
        $this->assertSame('Bebidas', $category->name);

        $this->from('/company/categories')
            ->put("/company/categories/{$category->id}", ['name' => 'Gaseosas', 'active' => '0'])
            ->assertRedirect('/company/categories');
        $this->assertFalse($category->fresh()->active);
        $this->assertSame('Gaseosas', $category->fresh()->name);

        $this->from('/company/categories')->delete("/company/categories/{$category->id}");
        $this->assertSoftDeleted($category);
    }

    public function test_name_is_unique_per_company_only(): void
    {
        $this->company->categories()->create(['name' => 'Snacks']);

        // Repetido en la misma empresa: vuelve al modal de crear con el error
        $this->from('/company/categories')
            ->post('/company/categories', ['name' => 'Snacks', '_modal' => 'create'])
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('_modal', 'create');

        // Otra empresa sí puede usar el mismo nombre
        $other = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secreto123']);
        $this->actingAs($other, 'company')
            ->post('/company/categories', ['name' => 'Snacks'])
            ->assertSessionHasNoErrors();
    }

    public function test_listing_shows_only_own_categories(): void
    {
        $this->company->categories()->create(['name' => 'Lácteos propios']);
        $other = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secreto123']);
        $other->categories()->create(['name' => 'Categoría ajena']);

        $this->get('/company/categories')
            ->assertOk()
            ->assertSee('Lácteos propios')
            ->assertDontSee('Categoría ajena');
    }

    public function test_cannot_edit_or_delete_another_companys_category(): void
    {
        $other    = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secreto123']);
        $category = $other->categories()->create(['name' => 'Ajena']);

        $this->put("/company/categories/{$category->id}", ['name' => 'Hackeada'])->assertNotFound();
        $this->delete("/company/categories/{$category->id}")->assertNotFound();
        $this->assertSame('Ajena', $category->fresh()->name);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $category = $this->company->categories()->create(['name' => 'Abarrotes']);
        $this->company->products()->create(['category_id' => $category->id, 'name' => 'Arroz', 'sale_price' => 4.5]);

        $this->from('/company/categories')
            ->delete("/company/categories/{$category->id}")
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($category);
    }
}
