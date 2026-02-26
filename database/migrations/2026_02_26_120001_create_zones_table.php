<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZonesTable extends Migration
{
    public function up()
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->text('obs')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zones');
    }
}
