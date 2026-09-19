<?php

namespace Tests\Feature;

use App\LoadItem;
use App\Order;
use App\Order_product;
use Database\Seeders\LegacyProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_populates_legacy_records_without_delivery_plans(): void
    {
        $this->seed(LegacyProductionSeeder::class);
        $this->seed(LegacyProductionSeeder::class);

        $orders = Order::where('order_number', 'like', 'PED-LEGADO-%')->get();
        $orderProducts = Order_product::whereIn('order_id', $orders->pluck('order_number'))->get();

        $this->assertCount(4, $orders);
        $this->assertCount(4, $orderProducts);
        $this->assertSame(0, $orderProducts->sum(fn ($item) => $item->deliveryPlans()->count()));

        $loadedItem = $orderProducts->firstWhere('order_id', 'PED-LEGADO-2001');
        $this->assertSame(['390' => 3], json_decode($loadedItem->carga, true));
        $this->assertTrue(LoadItem::where('order_product_id', $loadedItem->id)
            ->whereNull('delivery_plan_id')
            ->exists());
    }
}
