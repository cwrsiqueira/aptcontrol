<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Relaciona o pedido aprovado ao orçamento escolhido.
class AddApprovedQuoteIdToPurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Guarda o orçamento escolhido pelo aprovador.
            $table->foreignId('approved_quote_id')
                ->nullable()
                ->after('decision_note')
                ->constrained('purchase_quotes')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            // Remove o vínculo antes de remover a coluna.
            $table->dropForeign(['approved_quote_id']);
            $table->dropColumn('approved_quote_id');
        });
    }
}
