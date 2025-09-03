<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionLinksTable extends Migration
{
    public function up()
    {
        Schema::create('permission_links', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_permission_item');

            $table->timestamps();

            $table->foreign('id_user')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('id_permission_item')
                  ->references('id')->on('permission_items')
                  ->onDelete('cascade');

            $table->unique(['id_user', 'id_permission_item'], 'uq_user_permission');
        });
    }

    public function down()
    {
        Schema::dropIfExists('permission_links');
    }
}
