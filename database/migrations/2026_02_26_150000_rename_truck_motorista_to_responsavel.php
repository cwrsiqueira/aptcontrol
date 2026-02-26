<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameTruckMotoristaToResponsavel extends Migration
{
    public function up()
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->renameColumn('motorista', 'responsavel');
        });
    }

    public function down()
    {
        Schema::table('trucks', function (Blueprint $table) {
            $table->renameColumn('responsavel', 'motorista');
        });
    }
}
