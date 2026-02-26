<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddZoneToLoadItemsAndRefactorLoads extends Migration
{
    public function up()
    {
        Schema::table('load_items', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('order_product_id')->constrained('zones')->onDelete('set null');
            $table->string('zona_nome', 100)->nullable()->after('zone_id');
        });

        // Migrar zona dos loads para os load_items existentes
        $loads = DB::table('loads')->get();
        foreach ($loads as $load) {
            DB::table('load_items')->where('load_id', $load->id)->update([
                'zone_id' => $load->zone_id,
                'zona_nome' => $load->zona_nome,
            ]);
        }

        Schema::table('loads', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['zone_id', 'zona_nome']);
        });
    }

    public function down()
    {
        Schema::table('loads', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->string('zona_nome', 100)->nullable();
        });

        Schema::table('load_items', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['zone_id', 'zona_nome']);
        });
    }
}
