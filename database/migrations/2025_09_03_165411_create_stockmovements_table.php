<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockmovementsTable extends Migration
{
    public function up()
    {
        Schema::create('stockmovements', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('product_id');

            // Pedido lógico (opcional), por número
            $table->string('order_id', 50)->nullable();

            // 'in' | 'out' (texto simples)
            $table->string('movement_type', 10)->nullable();

            // origem livre: 'production', 'adjustment', 'order', etc.
            $table->string('movement_source', 30)->nullable();

            $table->date('movement_date');
            $table->decimal('movement_quant', 12, 3);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('restrict');

            $table->foreign('order_id')
                  ->references('order_number')->on('orders')
                  ->onDelete('set null');

            $table->index(['product_id', 'movement_date']);
            $table->index('movement_type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stockmovements');
    }
}
