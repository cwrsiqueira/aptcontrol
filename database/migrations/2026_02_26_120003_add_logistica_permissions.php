<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLogisticaPermissions extends Migration
{
    public function up()
    {
        $now = now();
        $perms = [
            ['slug' => 'menu-logistica', 'name' => 'Menu Logística', 'group_name' => 'Menus'],
            ['slug' => 'trucks.create', 'name' => 'Cadastrar Caminhão', 'group_name' => 'Logística'],
            ['slug' => 'trucks.view', 'name' => 'Visualizar Caminhão', 'group_name' => 'Logística'],
            ['slug' => 'trucks.update', 'name' => 'Editar Caminhão', 'group_name' => 'Logística'],
            ['slug' => 'trucks.delete', 'name' => 'Excluir Caminhão', 'group_name' => 'Logística'],
            ['slug' => 'zones.create', 'name' => 'Cadastrar Zona', 'group_name' => 'Logística'],
            ['slug' => 'zones.view', 'name' => 'Visualizar Zona', 'group_name' => 'Logística'],
            ['slug' => 'zones.update', 'name' => 'Editar Zona', 'group_name' => 'Logística'],
            ['slug' => 'zones.delete', 'name' => 'Excluir Zona', 'group_name' => 'Logística'],
        ];

        foreach ($perms as $p) {
            if (!DB::table('permission_items')->where('slug', $p['slug'])->exists()) {
                DB::table('permission_items')->insert([
                    'slug' => $p['slug'],
                    'name' => $p['name'],
                    'group_name' => $p['group_name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('permission_items')->whereIn('slug', [
            'menu-logistica', 'trucks.create', 'trucks.view', 'trucks.update', 'trucks.delete',
            'zones.create', 'zones.view', 'zones.update', 'zones.delete',
        ])->delete();
    }
}
