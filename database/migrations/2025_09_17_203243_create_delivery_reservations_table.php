<?php
// database/migrations/2025_09_17_000000_create_delivery_reservations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeliveryReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('delivery_reservations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('order_id')->nullable(); // origem opcional
            $table->unsignedBigInteger('product_id');
            $table->date('delivery_date'); // <- mesma semântica de order_products.delivery_date
            $table->unsignedInteger('quant');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('expires_at'); // TTL (ex.: now()+60min)

            $table->timestamps();

            // Índices úteis
            $table->index(['product_id', 'delivery_date']);
            $table->index(['user_id', 'delivery_date']);
            $table->index('expires_at');

            // Se quiser FKs e não estiver no SQLite, pode liberar:
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('order_id')->references('order_number')->on('orders')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_reservations');
    }
}
