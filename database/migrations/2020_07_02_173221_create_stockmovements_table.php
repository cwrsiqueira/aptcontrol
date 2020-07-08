<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockmovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stockmovements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products_table');
            $table->date('moviment_date');
            $table->integer('moviment_quant');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stockmovements');
    }
}
