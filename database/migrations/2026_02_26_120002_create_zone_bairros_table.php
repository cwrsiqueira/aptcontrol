<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZoneBairrosTable extends Migration
{
    public function up()
    {
        Schema::create('zone_bairros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->onDelete('cascade');
            $table->string('bairro_nome', 150);
            $table->timestamps();

            $table->unique(['zone_id', 'bairro_nome']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('zone_bairros');
    }
}
