<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');

            $table->string('action', 50);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name', 150)->nullable();
            $table->string('menu', 50)->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->index(['user_id', 'action']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
