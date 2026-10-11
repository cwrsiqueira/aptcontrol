<?php

namespace Tests\Feature;

use App\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductListReturnPageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Administrador',
            'email' => 'admin-teste@example.com',
            'password' => Hash::make('12345678'),
            'confirmed_user' => 1,
        ]);
    }

    public function test_list_links_carry_the_current_page_and_search(): void
    {
        foreach (range(1, 11) as $n) {
            Product::create([
                'name' => sprintf('Produto %02d', $n),
                'current_stock' => 0,
                'daily_production_forecast' => 1,
            ]);
        }

        $last = Product::where('name', 'Produto 11')->firstOrFail();

        $response = $this->actingAs($this->user)->get(route('products.index', [
            'page' => 2,
            'q' => 'Produto',
        ]));

        $response->assertOk();
        $response->assertSee(e(route('products.edit', [
            'product' => $last,
            'from_page' => 2,
            'from_q' => 'Produto',
        ])), false);
        $response->assertSee(e(route('products.create', [
            'from_page' => 2,
            'from_q' => 'Produto',
        ])), false);
        $response->assertSee('action="' . route('products.index') . '"', false);
    }

    public function test_save_cancel_and_delete_return_to_the_same_list_page(): void
    {
        $product = Product::create([
            'name' => 'Telha',
            'current_stock' => 0,
            'daily_production_forecast' => 1,
        ]);

        $origin = ['from_page' => 2, 'from_q' => 'Telha'];
        $list = route('products.index', ['page' => 2, 'q' => 'Telha']);

        $this->actingAs($this->user)
            ->get(route('products.edit', array_merge(['product' => $product], $origin)))
            ->assertOk()
            ->assertSee(e($list), false)
            ->assertSee('name="from_page"', false)
            ->assertSee('value="Telha"', false);

        $this->actingAs($this->user)
            ->put(route('products.update', $product), array_merge($origin, [
                'name' => 'Telha',
                'forecast' => '20',
            ]))
            ->assertRedirect($list);

        $this->actingAs($this->user)
            ->get(route('products.stocks.index', array_merge(['product' => $product], $origin)))
            ->assertOk()
            ->assertSee(e($list), false)
            ->assertSee('name="from_page"', false);

        $this->actingAs($this->user)
            ->get(route('cc_product', array_merge(['id' => $product->id], $origin)))
            ->assertOk()
            ->assertSee(e($list), false);

        $this->actingAs($this->user)
            ->delete(route('products.destroy', $product), $origin)
            ->assertRedirect($list);
    }
}
