<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoadItemsTable extends Migration
{
    public function up()
    {
        Schema::create('load_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('load_id')->constrained('loads')->onDelete('cascade');
            $table->foreignId('order_product_id')->constrained('order_products')->onDelete('cascade');
            $table->unsignedInteger('qtd_paletes')->default(0);
            $table->string('bairro', 150)->nullable();
            $table->timestamps();

            $table->index(['load_id', 'order_product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('load_items');
    }
}
