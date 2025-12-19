<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToOrderProducts extends Migration
{
    public function up()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->index(['order_id', 'product_id'], 'order_products_order_product_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['complete_order'], 'orders_complete_order_idx');
        });
    }

    public function down()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropIndex('order_products_order_product_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_complete_order_idx');
        });
    }
}
