<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToOrderProducts extends Migration
{
    public function up()
    {
        Schema::table('order_products', function (Blueprint $table) {
            // Evita nome duplicado se já existir
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = array_map(fn($i) => $i->getName(), $sm->listTableIndexes($table->getTable()));

            if (!in_array('order_products_order_product_idx', $indexes)) {
                $table->index(['order_id', 'product_id'], 'order_products_order_product_idx');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            // Útil para buscas por status
            if (Schema::hasColumn('orders', 'complete_order')) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_map(fn($i) => $i->getName(), $sm->listTableIndexes($table->getTable()));
                if (!in_array('orders_complete_order_idx', $indexes)) {
                    $table->index(['complete_order'], 'orders_complete_order_idx');
                }
            }
        });
    }

    public function down()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropIndex('order_products_order_product_idx');
        });
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'complete_order')) {
                $table->dropIndex('orders_complete_order_idx');
            }
        });
    }
}
