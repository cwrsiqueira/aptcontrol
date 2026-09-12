<?php

namespace Tests\Feature;

use App\LoadItem;
use App\Order;
use App\Order_product;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_populates_a_consistent_demonstration_database(): void
    {
        $this->seed(DemoDatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertDatabaseCount('sellers', 3);
        $this->assertDatabaseCount('clients', 5);
        $this->assertDatabaseCount('products', 5);
        $this->assertDatabaseCount('trucks', 3);
        $this->assertDatabaseCount('orders', 5);

        $orderProduct = Order_product::where('order_id', 'PED-DEMO-1001')
            ->where('quant', 1170)
            ->firstOrFail();
        $plans = $orderProduct->deliveryPlans;

        $this->assertCount(3, $plans);
        $this->assertSame([390.0, 390.0, 390.0], $plans->pluck('quantity')->map(fn ($value) => (float) $value)->all());
        $this->assertSame(3, $plans->pluck('delivery_date')->map->toDateString()->unique()->count());
        $this->assertSame(['390' => 1], $plans->first()->carga);

        $mixedPlan = Order_product::where('order_id', 'PED-DEMO-1001')
            ->where('quant', 650)
            ->firstOrFail()
            ->deliveryPlans
            ->first();
        $this->assertSame(['390' => 1, '260' => 1], $mixedPlan->carga);
        $mixedTotal = collect($mixedPlan->carga)
            ->map(fn ($count, $capacity) => $capacity * $count)
            ->sum();
        $this->assertSame(650, $mixedTotal);
        $this->assertSame($orderProduct->id, LoadItem::firstOrFail()->order_product_id);
        $this->assertSame($plans->first()->id, LoadItem::firstOrFail()->delivery_plan_id);
        $this->assertSame(1, Order::where('order_number', 'PED-DEMO-1004')->value('complete_order'));
    }
}
