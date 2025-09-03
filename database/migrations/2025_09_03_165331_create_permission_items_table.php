<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionItemsTable extends Migration
{
    public function up()
    {
        Schema::create('permission_items', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Ex.: 'clients.view', 'orders.edit', etc.
            $table->string('slug', 100)->unique();
            $table->string('name', 150);

            // O código usa "group_name" nas views/controllers
            $table->string('group_name', 100)->nullable();

            $table->timestamps();

            $table->index('group_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_items');
    }
}
