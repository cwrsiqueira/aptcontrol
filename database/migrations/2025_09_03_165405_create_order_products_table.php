<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProductsTable extends Migration
{
    public function up()
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Relaciona ao "número do pedido" e NÃO ao ID numérico
            $table->string('order_id', 50);

            $table->unsignedBigInteger('product_id');

            // quantidades aparecem como inteiras no front; mantenho 12,3 para flexibilidade
            $table->decimal('quant', 12, 3);

            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);

            $table->date('delivery_date')->nullable();

            $table->timestamps();

            // FK para products(id)
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('restrict');

            // FK para orders(order_number) — compatível com SQLite se a coluna referenciada for UNIQUE
            $table->foreign('order_id')
                  ->references('order_number')->on('orders')
                  ->onDelete('cascade');

            $table->index(['order_id', 'product_id']);
            $table->index('delivery_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_products');
    }
}
