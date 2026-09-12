<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProductDeliveryPlansTable extends Migration
{
    public function up()
    {
        Schema::create('order_product_delivery_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_product_id')->constrained('order_products')->onDelete('cascade'); //identifica qual produto do pedido pertence a essa entrega.
            $table->unsignedSmallInteger('sequence'); //ordem da entrega.
            $table->decimal('quantity', 12, 3); //quantidade do produto entregue nessa etapa. Aceita até três casas decimais.
            $table->date('delivery_date');
            $table->timestamps();

            $table->unique(['order_product_id', 'sequence']);
            $table->index('delivery_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_product_delivery_plans');
    }
}
