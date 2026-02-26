<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLoadsTable extends Migration
{
    public function up()
    {
        Schema::create('loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained('trucks')->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->string('zona_nome', 100)->nullable()->comment('Zona em texto quando zone_id é null');
            $table->string('status', 50)->default('montagem')->comment('montagem, fechada, enviada');
            $table->timestamp('data_montagem')->nullable();
            $table->text('obs')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loads');
    }
}
