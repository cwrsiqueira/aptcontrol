<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseQuotesTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->string('supplier_name', 150);
            $table->decimal('amount', 12, 2);
            $table->date('quote_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['purchase_order_id', 'quote_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('purchase_quotes');
    }
}
