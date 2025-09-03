<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('name', 100)->unique();
            $table->unsignedBigInteger('id_categoria'); // FK para clients_categories

            $table->string('contact', 50)->nullable();
            $table->text('full_address')->nullable();

            $table->timestamps();

            $table->foreign('id_categoria')
                  ->references('id')->on('clients_categories')
                  ->onDelete('restrict');

            $table->index('id_categoria');
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
}
