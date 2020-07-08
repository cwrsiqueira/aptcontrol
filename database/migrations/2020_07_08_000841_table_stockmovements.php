<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TableStockmovements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stockmovements', function (Blueprint $table) {
            $table->renameColumn('moviment_date', 'movement_date');
            $table->renameColumn('moviment_quant', 'movement_quant');
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
