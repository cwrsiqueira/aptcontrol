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

    // Cobre cadastro, fracionamento e montagem das cargas.

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

    private function upcomingWeekday(int $offset = 0): string
    {
        $date = now()->addDay()->startOfDay();
        $found = 0;

        while (true) {
            if (!$date->isSunday()) {
                if ($found === $offset) {
                    return $date->toDateString();
                }
                $found++;
            }
            $date->addDay();
        }
    }

    public function test_creates_one_order_item_with_equal_delivery_installments(): void
    {
        $firstDate = $this->upcomingWeekday(0);
        $secondDate = $this->upcomingWeekday(1);
        $thirdDate = $this->upcomingWeekday(2);

        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'quantity_per_delivery' => 5,
            'delivery_date' => $firstDate,
            'delivery_plan' => [
                ['quantity' => 5, 'date' => $secondDate],
                ['quantity' => 5, 'date' => $thirdDate],
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

    public function test_rejects_a_sunday_delivery(): void
    {
        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'quantity_per_delivery' => 5,
            'delivery_date' => '2026-10-10',
            'delivery_plan' => [
                ['quantity' => 5, 'date' => '2026-10-10'],
                ['quantity' => 5, 'date' => '2026-10-11'],
            ],
        ]);

        $response->assertSessionHasErrors('delivery_plan.1.date');
        $this->assertDatabaseCount('order_products', 0);
    }

    public function test_skips_sunday_when_the_next_delivery_is_calculated(): void
    {
        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'quantity_per_delivery' => 5,
            'delivery_date' => '2026-10-10',
        ])->assertRedirect();

        $dates = Order_product::first()->deliveryPlans()->orderBy('sequence')->pluck('delivery_date')
            ->map(fn ($date) => $date->toDateString())
            ->all();

        $this->assertSame(['2026-10-10', '2026-10-12'], $dates);
    }

    public function test_saves_custom_delivery_quantities(): void
    {
        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '100',
            'quantity_per_delivery' => 30,
            'delivery_date' => '2026-10-12',
            'delivery_plan' => [
                ['quantity' => 30, 'date' => '2026-10-12'],
                ['quantity' => 40, 'date' => '2026-10-13'],
                ['quantity' => 30, 'date' => '2026-10-14'],
            ],
        ])->assertRedirect();

        $quantities = Order_product::first()->deliveryPlans()->orderBy('sequence')->pluck('quantity')
            ->map(fn ($value) => (int) $value)
            ->all();

        $this->assertSame([30, 40, 30], $quantities);
        $this->assertSame(['30' => 2, '40' => 1], json_decode(Order_product::first()->carga, true));
    }

    public function test_rejects_repeated_delivery_dates(): void
    {
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'quantity_per_delivery' => 5,
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

    public function test_edit_page_asks_for_the_quantity_per_delivery(): void
    {
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => now()->addDay()->toDateString(),
        ]);
        $orderProduct->deliveryPlans()->createMany([
            ['sequence' => 1, 'quantity' => 6, 'delivery_date' => now()->addDay()->toDateString(), 'carga' => [6 => 1]],
            ['sequence' => 2, 'quantity' => 4, 'delivery_date' => now()->addDays(2)->toDateString(), 'carga' => [4 => 1]],
        ]);

        $this->actingAs($this->user)
            ->get(route('order_products.edit', ['order_product' => $orderProduct, 'order' => $this->order->id]))
            ->assertOk()
            ->assertSee('Quantidade por entrega')
            ->assertSee('value="6"', false);
    }

    public function test_create_page_asks_for_the_quantity_per_delivery(): void
    {
        $this->actingAs($this->user)
            ->get(route('order_products.create', ['order' => $this->order->id]))
            ->assertOk()
            ->assertSee('Quantidade por entrega')
            ->assertSee('A última fica com a sobra')
            ->assertSee('Capacidade do palete');
    }

    public function test_requires_a_quantity_per_delivery(): void
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
            ]],
        ]);

        $response->assertSessionHasErrors('quantity_per_delivery');
        $this->assertDatabaseCount('order_products', 0);
    }

    public function test_splits_an_exact_quantity_into_one_pallet_per_delivery(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '12',
            'quantity_per_delivery' => 6,
            'delivery_date' => $date,
            'delivery_plan' => [[
                'date' => $date,
                'palete_tipo' => [999],
                'palete_quant' => [999],
            ]],
        ])->assertRedirect();

        $this->assertDatabaseCount('order_product_delivery_plans', 2);
        $this->assertDatabaseHas('order_product_delivery_plans', [
            'quantity' => 6,
            'carga' => json_encode([6 => 1]),
        ]);
        $this->assertSame(['6' => 2], json_decode(Order_product::first()->carga, true));
    }

    public function test_puts_the_remainder_on_the_last_delivery(): void
    {
        $date = $this->upcomingWeekday(0);
        $nextDate = $this->upcomingWeekday(1);

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'quantity_per_delivery' => 6,
            'delivery_date' => $date,
            'delivery_plan' => [
                ['date' => $date],
                ['date' => $nextDate],
            ],
        ])->assertRedirect();

        $quantities = Order_product::first()->deliveryPlans()->orderBy('sequence')->pluck('quantity')
            ->map(fn ($value) => (int) $value)
            ->all();
        $this->assertSame([6, 4], $quantities);
        $this->assertDatabaseHas('order_product_delivery_plans', [
            'sequence' => 2,
            'quantity' => 4,
            'carga' => json_encode([4 => 1]),
        ]);
    }

    public function test_splits_a_large_quantity_and_keeps_the_remainder_on_the_last_delivery(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '84327',
            'quantity_per_delivery' => 2219,
            'delivery_date' => $date,
        ])->assertRedirect();

        $item = Order_product::first();
        $quantities = $item->deliveryPlans()->orderBy('sequence')->pluck('quantity')
            ->map(fn ($value) => (int) $value)
            ->all();

        $this->assertCount(39, $quantities);
        $this->assertSame(array_fill(0, 38, 2219), array_slice($quantities, 0, 38));
        $this->assertSame(5, $quantities[38]);
        $this->assertSame(['2219' => 38, '5' => 1], json_decode($item->carga, true));
    }

    public function test_uses_a_single_delivery_when_the_lot_is_larger_than_the_total(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '10',
            'quantity_per_delivery' => 25,
            'delivery_date' => $date,
        ])->assertRedirect();

        $this->assertDatabaseCount('order_product_delivery_plans', 1);
        $this->assertDatabaseHas('order_product_delivery_plans', [
            'quantity' => 10,
            'carga' => json_encode([10 => 1]),
        ]);
    }

    public function test_rejects_more_than_one_hundred_deliveries(): void
    {
        $date = now()->addDay()->toDateString();

        $response = $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '101',
            'quantity_per_delivery' => 1,
            'delivery_date' => $date,
        ]);

        $response->assertSessionHasErrors('quantity_per_delivery');
        $this->assertDatabaseCount('order_products', 0);
        $this->assertDatabaseCount('order_product_delivery_plans', 0);
    }

    public function test_reports_scheduled_deliveries_for_the_selected_date(): void
    {
        $date = now()->addDays(3)->toDateString();

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

        $this->actingAs($this->user)
            ->getJson(route('order_products.truck_availability', ['date' => $date]))
            ->assertOk()
            ->assertExactJson([
                'planned' => 2,
            ]);
    }

    public function test_displays_legacy_pallets_when_the_item_has_no_delivery_plan(): void
    {
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 780,
            'delivery_date' => now()->addDay()->toDateString(),
        ]);
        $orderProduct->carga = json_encode([390 => 2]);
        $orderProduct->save();

        $this->actingAs($this->user)
            ->get(route('cc_product', ['id' => $this->product->id]))
            ->assertOk()
            ->assertSee('2 pal. × 390')
            ->assertDontSee('Cadastro anterior');
    }

    public function test_displays_each_delivery_plan_in_its_own_table_row(): void
    {
        $firstDate = now()->addDay();
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 2080,
            'delivery_date' => $firstDate->toDateString(),
            'carga' => json_encode([520 => 4]),
        ]);

        $plans = collect(range(0, 3))->map(function ($offset) use ($orderProduct, $firstDate) {
            return $orderProduct->deliveryPlans()->create([
                'sequence' => $offset + 1,
                'quantity' => 520,
                'delivery_date' => $firstDate->copy()->addDays($offset)->toDateString(),
                'carga' => [520 => 1],
            ]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('cc_product', ['id' => $this->product->id]))
            ->assertOk();

        $html = $response->getContent();
        $this->assertSame(4, substr_count($html, '<tr data-order-product-id="' . $orderProduct->id . '"'));
        $this->assertSame(4, substr_count($html, '1 pal. × 520'));
        $this->assertStringNotContainsString('<strong>Entrega', $html);

        foreach ($plans as $plan) {
            $this->assertStringContainsString('data-delivery-plan-id="' . $plan->id . '"', $html);
            $this->assertStringContainsString($plan->delivery_date->format('d/m/Y'), $html);
        }
    }

    public function test_lists_deliveries_by_the_date_shown_when_it_differs_from_the_item_date(): void
    {
        $this->order->update(['zona' => 'A', 'bairro' => 'A']);

        $laterOnScreen = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => now()->addDay()->toDateString(),
        ]);
        $laterOnScreen->deliveryPlans()->create([
            'sequence' => 1,
            'quantity' => 10,
            'delivery_date' => now()->addDays(10)->toDateString(),
        ]);

        $earlierOrder = Order::create([
            'client_id' => $this->order->client_id,
            'order_number' => 'PED-200',
            'order_date' => now()->toDateString(),
            'withdraw' => 'entregar',
            'complete_order' => 0,
            'zona' => 'Z',
            'bairro' => 'Z',
        ]);
        $earlierOnScreen = Order_product::create([
            'order_id' => $earlierOrder->order_number,
            'product_id' => $this->product->id,
            'quant' => 10,
            'delivery_date' => now()->addDays(6)->toDateString(),
        ]);
        $earlierOnScreen->deliveryPlans()->create([
            'sequence' => 1,
            'quantity' => 10,
            'delivery_date' => now()->addDays(2)->toDateString(),
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('cc_product', ['id' => $this->product->id]))
            ->assertOk()
            ->getContent();

        $earlierPosition = strpos($html, '#' . $earlierOrder->order_number);
        $laterPosition = strpos($html, '#' . $this->order->order_number);

        $this->assertNotFalse($earlierPosition);
        $this->assertNotFalse($laterPosition);
        $this->assertLessThan($laterPosition, $earlierPosition);
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

    public function test_puts_the_remainder_on_the_last_delivery_when_the_item_quantity_changes(): void
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

        $response->assertRedirect(route('order_products.index', ['order' => $this->order->id]));
        $this->assertDatabaseHas('order_products', [
            'id' => $orderProduct->id,
            'quant' => 9,
        ]);
        $this->assertSame(
            [4.0, 5.0],
            $orderProduct->deliveryPlans()->orderBy('sequence')->pluck('quantity')->map(fn ($value) => (float) $value)->all()
        );
    }

    public function test_rejects_free_space_when_editing_a_delivery_plan(): void
    {
        $date = now()->addDay()->toDateString();
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

        $response = $this->actingAs($this->user)->put(route('order_products.update', $orderProduct), [
            'order' => $this->order->id,
            'order_id' => $this->order->id,
            'quant' => '10',
            'delivery_date' => $date,
            'delivery_plan' => [[
                'id' => $plan->id,
                'quantity' => 10,
                'date' => $date,
                'palete_tipo' => [6],
                'palete_quant' => [2],
            ]],
        ]);

        $response->assertSessionHasErrors('delivery_plan.0.paletes');
        $this->assertSame(['5' => 2], $plan->fresh()->carga);
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
                    'date' => $this->upcomingWeekday(3),
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

    public function test_splits_a_delivery_into_full_pallets_and_a_remainder_pallet(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '2219',
            'quantity_per_delivery' => 2219,
            'delivery_date' => $date,
            'delivery_plan' => [[
                'quantity' => 2219,
                'date' => $date,
                'pallet_capacity' => 261,
            ]],
        ])->assertRedirect();

        $plan = Order_product::first()->deliveryPlans()->first();
        $this->assertSame(['261' => 8, '131' => 1], $plan->carga);
        $this->assertSame(9, $plan->total_paletes);
    }

    public function test_uses_only_full_pallets_when_the_capacity_divides_the_delivery(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '522',
            'quantity_per_delivery' => 522,
            'delivery_date' => $date,
            'delivery_plan' => [[
                'quantity' => 522,
                'date' => $date,
                'pallet_capacity' => 261,
            ]],
        ])->assertRedirect();

        $this->assertSame(['261' => 2], Order_product::first()->deliveryPlans()->first()->carga);
    }

    public function test_keeps_one_pallet_when_the_capacity_is_empty(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '2219',
            'quantity_per_delivery' => 2219,
            'delivery_date' => $date,
            'delivery_plan' => [[
                'quantity' => 2219,
                'date' => $date,
            ]],
        ])->assertRedirect();

        $this->assertSame(['2219' => 1], Order_product::first()->deliveryPlans()->first()->carga);
    }

    public function test_uses_one_pallet_when_capacity_is_greater_than_the_delivery(): void
    {
        $date = now()->addDay()->toDateString();

        $this->actingAs($this->user)->post(route('order_products.store'), [
            'order' => $this->order->id,
            'product_name' => $this->product->name,
            'quant' => '100',
            'quantity_per_delivery' => 100,
            'delivery_date' => $date,
            'delivery_plan' => [[
                'quantity' => 100,
                'date' => $date,
                'pallet_capacity' => 261,
            ]],
        ])->assertRedirect();

        $this->assertSame(['100' => 1], Order_product::first()->deliveryPlans()->first()->carga);
    }

    public function test_edit_page_fills_the_pallet_capacity_of_a_split_delivery(): void
    {
        $orderProduct = Order_product::create([
            'order_id' => $this->order->order_number,
            'product_id' => $this->product->id,
            'quant' => 2219,
            'delivery_date' => now()->addDay()->toDateString(),
        ]);
        $orderProduct->deliveryPlans()->create([
            'sequence' => 1,
            'quantity' => 2219,
            'delivery_date' => now()->addDay()->toDateString(),
            'carga' => [261 => 8, 131 => 1],
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('order_products.edit', ['order_product' => $orderProduct, 'order' => $this->order->id]))
            ->assertOk()
            ->assertSee('Capacidade do palete')
            ->getContent();

        preg_match('/data-initial-plan="([^"]+)"/', $html, $matches);
        $plan = json_decode(base64_decode($matches[1]), true);

        $this->assertSame(261, $plan[0]['pallet_capacity']);
    }

    public function test_does_not_allow_a_loaded_installment_to_change_pallet_capacity(): void
    {
        $date = $this->upcomingWeekday(2);
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
                    'date' => $date,
                    'pallet_capacity' => 3,
                ]],
            ]);

        $response->assertSessionHasErrors('delivery_plan.0');
        $this->assertSame(['5' => 2], $plan->fresh()->carga);
    }
}
