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
            $table->dropForeign(['id_permission_item']);
            $table->dropUnique('uq_user_permission');
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
            $table->foreign('id_permission_item')
                ->references('id')->on('permission_items')
                ->onDelete('cascade');
            $table->unique(['id_user', 'id_permission_item'], 'uq_user_permission');
        });
    }
}
