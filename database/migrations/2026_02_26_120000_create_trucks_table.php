<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrucksTable extends Migration
{
    public function up()
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('motorista', 150);
            $table->unsignedInteger('capacidade_paletes')->default(0)->comment('Capacidade em paletes');
            $table->string('modelo', 100)->nullable();
            $table->string('placa', 20)->nullable();
            $table->text('obs')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('trucks');
    }
}
