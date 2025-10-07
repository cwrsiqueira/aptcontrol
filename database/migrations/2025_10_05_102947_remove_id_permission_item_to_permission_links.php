<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveIdPermissionItemToPermissionLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('permission_links', function (Blueprint $table) {
            $table->dropColumn('id_permission_item');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permission_links', function (Blueprint $table) {
            $table->unsignedBigInteger('id_permission_item')->nullable()->after('id_user');
        });
    }
}
