<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMotoristaToLoads extends Migration
{
    public function up()
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->string('motorista', 150)->nullable()->after('truck_id');
        });
    }

    public function down()
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->dropColumn('motorista');
        });
    }
}
