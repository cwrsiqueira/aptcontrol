<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddPurchasePermissions extends Migration
{
    public function up()
    {
        $now = now();
        $permissions = [
            ['slug' => 'menu-compras', 'name' => 'Menu Compras', 'group_name' => 'Menus'],
            ['slug' => 'purchases.create', 'name' => 'Cadastrar Pedido de Compra', 'group_name' => 'Compras'],
            ['slug' => 'purchases.view', 'name' => 'Visualizar Pedido de Compra', 'group_name' => 'Compras'],
            ['slug' => 'purchases.update', 'name' => 'Editar Pedido de Compra', 'group_name' => 'Compras'],
            ['slug' => 'purchases.delete', 'name' => 'Excluir Pedido de Compra', 'group_name' => 'Compras'],
            ['slug' => 'purchases.quotes', 'name' => 'Gerenciar Orçamentos de Compra', 'group_name' => 'Compras'],
            ['slug' => 'purchases.approve', 'name' => 'Aprovar ou Reprovar Pedido de Compra', 'group_name' => 'Compras'],
            ['slug' => 'purchases.finalize', 'name' => 'Finalizar Pedido de Compra', 'group_name' => 'Compras'],
        ];

        foreach ($permissions as $permission) {
            if (!DB::table('permission_items')->where('slug', $permission['slug'])->exists()) {
                DB::table('permission_items')->insert($permission + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $slugs = [
            'menu-compras',
            'purchases.create',
            'purchases.view',
            'purchases.update',
            'purchases.delete',
            'purchases.quotes',
            'purchases.approve',
            'purchases.finalize',
        ];

        DB::table('permission_links')->whereIn('slug_permission_item', $slugs)->delete();
        DB::table('permission_items')->whereIn('slug', $slugs)->delete();
    }
}
