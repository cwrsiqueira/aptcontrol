<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('client_id');
            $table->date('order_date');

            // chave lógica usada em vários joins; precisa ser UNIQUE para FK funcionar no SQLite
            $table->string('order_number', 50)->unique();

            $table->decimal('order_total', 12, 2)->default(0);

            $table->string('payment', 50);   // forma de pagamento
            $table->string('withdraw', 50);  // retirada/envio (texto)
            $table->tinyInteger('complete_order')->default(0); // 0=pending,1=delivered,2=canceled

            $table->timestamps();

            $table->foreign('client_id')
                  ->references('id')->on('clients')
                  ->onDelete('restrict');

            $table->index(['client_id', 'complete_order']);
            $table->index('order_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
