<?php

namespace Tests\Feature;

use App\Client;
use App\Order;
use App\Order_product;
use App\Product;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->makeUser([
            'name' => 'Administrador',
            'email' => 'admin-soft-delete@example.com',
            'confirmed_user' => 1,
        ]);
    }

    public function test_soft_deletes_product_with_order_links_and_keeps_name_on_order(): void
    {
        DB::table('clients_categories')->insert([
            'id' => 1,
            'name' => 'Teste',
        ]);

        $client = Client::create([
            'name' => 'Cliente Soft Delete',
            'id_categoria' => 1,
        ]);

        $order = Order::create([
            'client_id' => $client->id,
            'order_number' => 'PED-SOFT-1',
            'order_date' => now()->toDateString(),
            'withdraw' => 'entregar',
            'complete_order' => 0,
        ]);

        $product = Product::create([
            'name' => 'TIJOLAO METADE',
            'current_stock' => 0,
            'daily_production_forecast' => 1,
        ]);

        $orderProduct = Order_product::create([
            'order_id' => $order->order_number,
            'product_id' => $product->id,
            'quant' => 100,
            'delivery_date' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);

        $this->actingAs($this->user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('TIJOLAO METADE', false);

        $this->assertDatabaseHas('order_products', [
            'id' => $orderProduct->id,
            'product_id' => $product->id,
        ]);

        $restoredName = Order_product::find($orderProduct->id)->product->name;
        $this->assertStringContainsString('TIJOLAO METADE', $restoredName);
        $this->assertStringContainsString('[excluído #' . $product->id . ']', $restoredName);

        // Nome original liberado para novo cadastro.
        $recreated = Product::create([
            'name' => 'TIJOLAO METADE',
            'current_stock' => 0,
            'daily_production_forecast' => 1,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $recreated->id,
            'name' => 'TIJOLAO METADE',
            'deleted_at' => null,
        ]);
    }
}
