<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cria os itens solicitados em cada pedido de compra.
class CreatePurchaseOrderItemsTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            // Exclui os itens junto com o pedido ao qual pertencem.
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->string('description', 255);
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->index('purchase_order_id');
        });
    }

    public function down()
    {
        // Remove os itens do módulo de compras.
        Schema::dropIfExists('purchase_order_items');
    }
}
