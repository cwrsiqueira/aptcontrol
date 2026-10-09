<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddReleaseMarksToDeliveryPlans extends Migration
{
    public function up()
    {
        Schema::table('order_product_delivery_plans', function (Blueprint $table) {
            $table->integer('checkmark')->default(0);
            $table->boolean('favorite_delivery')->default(false);
        });

        self::copySingleDeliveryMarks();
    }

    public function down()
    {
        Schema::table('order_product_delivery_plans', function (Blueprint $table) {
            $table->dropColumn(['checkmark', 'favorite_delivery']);
        });
    }

    // Copia a marca do produto só quando existe uma entrega. Várias datas começam sem marca.
    public static function copySingleDeliveryMarks(): void
    {
        $singleProductIds = DB::table('order_product_delivery_plans')
            ->select('order_product_id')
            ->groupBy('order_product_id')
            ->havingRaw('COUNT(*) = 1')
            ->pluck('order_product_id');

        if ($singleProductIds->isEmpty()) {
            return;
        }

        $products = DB::table('order_products')
            ->whereIn('id', $singleProductIds)
            ->get(['id', 'checkmark', 'favorite_delivery']);

        foreach ($products as $product) {
            DB::table('order_product_delivery_plans')
                ->where('order_product_id', $product->id)
                ->update([
                    'checkmark' => (int) $product->checkmark,
                    'favorite_delivery' => (int) $product->favorite_delivery,
                ]);
        }
    }
}
