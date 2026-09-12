<?php

namespace Tests\Feature;

use App\Client;
use App\Load;
use App\LoadItem;
use App\Order;
use App\Order_product;
use App\Product;
use App\Truck;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrderProductDeliveryPlanningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Order $order;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('clients_categories')->insert([
            'id' => 1,
            'name' => 'Teste',
        ]);

        $client = Client::create([
            'name' => 'Cliente Teste',
            'id_categoria' => 1,
        ]);

        $this->order = Order::create([
            'client_id' => $client->id,
            'order_number' => 'PED-100',
            'order_date' => now()->toDateString(),
            'withdraw' => 'entregar',
            'complete_order' => 0,
        ]);

        $this->product = Product::create([
            'name' => 'Produto Teste',
            'current_stock' => 0,
            'daily_production_forecast' => 0,
        ]);

        $this->user = User::create([
            'name' => 'Administrador',
            'email' => 'admin-teste@example.com',
            'password' => Hash::make('12345678'),
            'confirmed_user' => 1,
        ]);
    }

    public function test_creates_one_order_item_with_equal_delivery_installments(): void
    {
        $firstDate = now()->addDay()->toDateString();
        $secondDate = now()->addDays(2)->toDateString();
        $thirdDate = now()->addDays(3)->toDateString();

        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'delivery_date' => $firstDate,
            'delivery_plan' => [
                ['quantity' => 5, 'date' => $secondDate, 'palete_tipo' => [5], 'palete_quant' => [1]],
                ['quantity' => 5, 'date' => $thirdDate, 'palete_tipo' => [5], 'palete_quant' => [1]],
            ],
        ]);

        $response->assertRedirect(route('order_products.index', ['order' => $this->order]));
        $this->assertDatabaseCount('order_products', 1);
        $this->assertDatabaseHas('order_products', [
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $firstDate,
        ]);
        $this->assertDatabaseCount('order_product_delivery_plans', 2);
        $this->assertDatabaseHas('order_product_delivery_plans', [
            'sequence' => 1,
            'quantity' => 5,
            'delivery_date' => $secondDate . ' 00:00:00',
            'carga' => json_encode([5 => 1]),
        ]);
        $this->assertSame(['5' => 2], json_decode(Order_product::first()->carga, true));
    }

    public function test_rejects_a_plan_with_unequal_installments(): void
    {
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($this->user)->from(route('order_products.create', ['order' => $this->order]))
            ->post(route('order_products.store'), [
                'order' => $this->order->id,
                'product_name' => $this->product->name,
                'quant' => '10',
                'delivery_date' => $date,
                'delivery_plan' => [
                    ['quantity' => 4, 'date' => $date],
                    ['quantity' => 6, 'date' => $date],
                ],
            ]);

        $response->assertSessionHasErrors('delivery_plan');
        $this->assertDatabaseCount('order_products', 0);
        $this->assertDatabaseCount('order_product_delivery_plans', 0);
    }

    public function test_rejects_repeated_delivery_dates(): void
    {
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'delivery_date' => $date,
            'delivery_plan' => [
                ['quantity' => 5, 'date' => $date],
                ['quantity' => 5, 'date' => $date],
            ],
        ]);

        $response->assertSessionHasErrors('delivery_plan.0.date');
        $this->assertDatabaseCount('order_products', 0);
        $this->assertDatabaseCount('order_product_delivery_plans', 0);
    }

    public function test_rejects_an_incomplete_pallet_pair(): void
    {
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'delivery_date' => $date,
            'delivery_plan' => [[
                'quantity' => 10,
                'date' => $date,
                'palete_tipo' => [5],
                'palete_quant' => [''],
            ]],
        ]);

        $response->assertSessionHasErrors('delivery_plan.0.paletes');
        $this->assertDatabaseCount('order_products', 0);
    }

    public function test_reports_truck_availability_for_the_selected_date(): void
    {
        $date = now()->addDays(3)->toDateString();
        $trucks = collect([
            Truck::create(['responsavel' => 'A', 'capacidade_paletes' => 10]),
            Truck::create(['responsavel' => 'B', 'capacidade_paletes' => 10]),
            Truck::create(['responsavel' => 'C', 'capacidade_paletes' => 10]),
        ]);

        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $date,
        ]);
        $orderProduct->deliveryPlans()->createMany([
            ['sequence' => 1, 'quantity' => 5, 'delivery_date' => $date],
            ['sequence' => 2, 'quantity' => 5, 'delivery_date' => $date],
        ]);
        Load::create([
            'truck_id' => $trucks->first()->id,
            'status' => 'montagem',
            'data_montagem' => $date . ' 08:00:00',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('order_products.truck_availability', ['date' => $date]))
            ->assertOk()
            ->assertJson([
                'total' => 3,
                'occupied' => 1,
                'planned' => 2,
                'available' => 1,
            ]);
    }

    public function test_keeps_installments_equal_when_the_item_quantity_changes(): void
    {
        $date = now()->addDay()->toDateString();
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $date,
        ]);
        $orderProduct->deliveryPlans()->createMany([
            ['sequence' => 1, 'quantity' => 5, 'delivery_date' => $date],
            ['sequence' => 2, 'quantity' => 5, 'delivery_date' => $date],
        ]);

        $response = $this->actingAs($this->user)->put(route('order_products.update', $orderProduct), [
            'order' => $this->order->id,
            'order_id' => $this->order->id,
            'quant' => '12',
            'delivery_date' => $date,
        ]);

        $response->assertRedirect(route('order_products.index', ['order' => $this->order->id]));
        $this->assertDatabaseHas('order_products', [
            'id' => $orderProduct->id,
            'quant' => 12,
        ]);
        $this->assertSame(
            [6.0, 6.0],
            $orderProduct->deliveryPlans()->pluck('quantity')->map(fn ($value) => (float) $value)->all()
        );
    }

    public function test_rejects_an_item_quantity_incompatible_with_its_delivery_count(): void
    {
        $date = now()->addDay()->toDateString();
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $date,
        ]);
        $orderProduct->deliveryPlans()->createMany([
            ['sequence' => 1, 'quantity' => 5, 'delivery_date' => $date],
            ['sequence' => 2, 'quantity' => 5, 'delivery_date' => $date],
        ]);

        $response = $this->actingAs($this->user)->put(route('order_products.update', $orderProduct), [
            'order' => $this->order->id,
            'order_id' => $this->order->id,
            'quant' => '9',
            'delivery_date' => $date,
        ]);

        $response->assertSessionHasErrors('quant');
        $this->assertDatabaseHas('order_products', [
            'id' => $orderProduct->id,
            'quant' => 10,
        ]);
        $this->assertSame(
            [5.0, 5.0],
            $orderProduct->deliveryPlans()->pluck('quantity')->map(fn ($value) => (float) $value)->all()
        );
    }

    public function test_adds_only_the_selected_installment_to_a_load_on_its_delivery_date(): void
    {
        $date = now()->addDays(4)->toDateString();
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $date,
            'carga' => json_encode([2 => 5]),
        ]);
        $plan = $orderProduct->deliveryPlans()->create([
            'sequence' => 1,
            'quantity' => 5,
            'delivery_date' => $date,
            'carga' => [2 => 5],
        ]);
        $truck = Truck::create([
            'responsavel' => 'Motorista Teste',
            'capacidade_paletes' => 10,
            'modelo' => 'Caminhão Teste',
        ]);

        $response = $this->actingAs($this->user)->postJson(route('cc.add_to_load'), [
            'order_product_id' => $orderProduct->id,
            'delivery_plan_id' => $plan->id,
            'truck_id' => $truck->id,
            'qtd_paletes' => 5,
        ]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('load_items', [
            'order_product_id' => $orderProduct->id,
            'delivery_plan_id' => $plan->id,
            'qtd_paletes' => 5,
        ]);
        $this->assertSame($date, Load::first()->data_montagem->toDateString());
    }

    public function test_does_not_allow_a_loaded_installment_to_be_changed(): void
    {
        $date = now()->addDays(2)->toDateString();
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $date,
            'carga' => json_encode([5 => 2]),
        ]);
        $plan = $orderProduct->deliveryPlans()->create([
            'sequence' => 1,
            'quantity' => 10,
            'delivery_date' => $date,
            'carga' => [5 => 2],
        ]);
        $truck = Truck::create([
            'responsavel' => 'Motorista Teste',
            'capacidade_paletes' => 10,
        ]);
        $load = Load::create([
            'truck_id' => $truck->id,
            'status' => 'montagem',
            'data_montagem' => $date,
        ]);
        LoadItem::create([
            'load_id' => $load->id,
            'order_product_id' => $orderProduct->id,
            'delivery_plan_id' => $plan->id,
            'qtd_paletes' => 1,
        ]);

        $response = $this->actingAs($this->user)->from(route('order_products.edit', $orderProduct))
            ->put(route('order_products.update', $orderProduct), [
                'order_id' => $this->order->id,
                'quant' => '10',
                'delivery_date' => $date,
                'delivery_plan' => [[
                    'id' => $plan->id,
                    'quantity' => 10,
                    'date' => now()->addDays(3)->toDateString(),
                    'palete_tipo' => [5],
                    'palete_quant' => [2],
                ]],
            ]);

        $response->assertSessionHasErrors('delivery_plan.0');
        $this->assertSame($date, $plan->fresh()->delivery_date->toDateString());
    }

    public function test_removes_only_the_selected_installment_from_a_load(): void
    {
        $date = now()->addDays(2)->toDateString();
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => $date,
        ]);
        $plans = collect([
            $orderProduct->deliveryPlans()->create(['sequence' => 1, 'quantity' => 5, 'delivery_date' => $date, 'carga' => [5 => 1]]),
            $orderProduct->deliveryPlans()->create(['sequence' => 2, 'quantity' => 5, 'delivery_date' => now()->addDays(3)->toDateString(), 'carga' => [5 => 1]]),
        ]);
        $truck = Truck::create(['responsavel' => 'Motorista Teste', 'capacidade_paletes' => 10]);
        $load = Load::create(['truck_id' => $truck->id, 'status' => 'montagem', 'data_montagem' => $date]);
        foreach ($plans as $plan) {
            LoadItem::create([
                'load_id' => $load->id,
                'order_product_id' => $orderProduct->id,
                'delivery_plan_id' => $plan->id,
                'qtd_paletes' => 1,
            ]);
        }

        $this->actingAs($this->user)->post(route('cc.carga_load_remover', [$load, $orderProduct]), [
            'delivery_plan_id' => $plans[0]->id,
        ])->assertRedirect();

        $this->assertDatabaseMissing('load_items', ['delivery_plan_id' => $plans[0]->id]);
        $this->assertDatabaseHas('load_items', ['delivery_plan_id' => $plans[1]->id]);
    }
}
