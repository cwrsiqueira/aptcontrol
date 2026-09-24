<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adiciona ao orçamento os dados do arquivo armazenado de forma privada.
class AddAttachmentFieldsToPurchaseQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('purchase_quotes', function (Blueprint $table) {
            // Guarda localização, nome original, tipo e tamanho do arquivo.
            $table->string('attachment_path')->nullable()->after('notes');
            $table->string('attachment_original_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 100)->nullable()->after('attachment_original_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
        });
    }

    public function down()
    {
        Schema::table('purchase_quotes', function (Blueprint $table) {
            // Remove somente os campos usados pelo anexo.
            $table->dropColumn([
                'attachment_path',
                'attachment_original_name',
                'attachment_mime',
                'attachment_size',
            ]);
        });
    }
}
