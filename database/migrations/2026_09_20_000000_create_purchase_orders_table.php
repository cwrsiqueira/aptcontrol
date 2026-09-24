<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cria a tabela principal e registra o andamento do pedido de compra.
class CreatePurchaseOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            // Guarda a solicitação e seus responsáveis.
            $table->foreignId('requester_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('request_date');
            $table->text('description');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');

            // Registra a decisão de aprovação ou reprovação.
            $table->foreignId('decided_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();

            // Registra quem encerrou o processo de compra.
            $table->foreignId('finalized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            // Otimiza as consultas por etapa e data.
            $table->index(['status', 'request_date']);
        });
    }

    public function down()
    {
        // Remove a estrutura principal do módulo.
        Schema::dropIfExists('purchase_orders');
    }
}
