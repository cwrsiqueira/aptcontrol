<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnderecoBairroZonaToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('endereco')->nullable()->after('withdraw');
            $table->string('bairro')->nullable()->after('endereco');
            $table->string('zona')->nullable()->after('bairro');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['endereco', 'bairro', 'zona']);
        });
    }
}
