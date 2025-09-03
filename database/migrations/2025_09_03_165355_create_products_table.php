<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 100)->unique();
            $table->integer('current_stock')->default(0)->nullable();
            $table->integer('daily_production_forecast'); // required nos validators
            $table->string('img_url')->nullable();

            $table->timestamps();

            $table->index('daily_production_forecast');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
