<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class WipePermissionItemsWithRollback extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('permission_links')->delete();
        DB::table('permission_items')->delete();
        DB::statement("DELETE FROM sqlite_sequence WHERE name = 'permission_items'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('permission_links')->delete();
        DB::table('permission_items')->delete();
        DB::statement("DELETE FROM sqlite_sequence WHERE name = 'permission_items'");

        DB::table('permission_items')->insert([
            ['id' => 1,  'slug' => 'menu-produtos',          'name' => 'Menu Produtos',                     'group_name' => 'Menus',      'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 2,  'slug' => 'menu-clientes',          'name' => 'Menu Clientes',                     'group_name' => 'Menus',      'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 3,  'slug' => 'menu-pedidos',           'name' => 'Menu Pedidos',                      'group_name' => 'Menus',      'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 4,  'slug' => 'menu-relatorios',        'name' => 'Menu Relatórios',                   'group_name' => 'Menus',      'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 5,  'slug' => 'menu-integracoes',       'name' => 'Menu Integrações',                  'group_name' => 'Menus',      'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 7,  'slug' => 'products.create',        'name' => 'Cadastrar Produto',                 'group_name' => 'Produtos',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 8,  'slug' => 'products.update',        'name' => 'Editar Produto',                    'group_name' => 'Produtos',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 9,  'slug' => 'products.stock',         'name' => '+ Estoque (Produto)',               'group_name' => 'Produtos',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 10, 'slug' => 'products.cc',            'name' => 'Conta Corrente (Produto)',          'group_name' => 'Produtos',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 11, 'slug' => 'products.delete',        'name' => 'Excluir Produto',                   'group_name' => 'Produtos',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 12, 'slug' => 'clients.create',         'name' => 'Cadastrar Cliente',                 'group_name' => 'Clientes',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 13, 'slug' => 'clients.update',         'name' => 'Editar Cliente',                    'group_name' => 'Clientes',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 14, 'slug' => 'orders.create',          'name' => 'Efetuar Pedido',                    'group_name' => 'Pedidos',    'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 15, 'slug' => 'clients.cc',             'name' => 'Conta Corrente (Cliente)',          'group_name' => 'Clientes',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 16, 'slug' => 'orders.view_completed',  'name' => 'Mostrar Pedidos Concluídos',        'group_name' => 'Pedidos',    'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 17, 'slug' => 'orders.view',            'name' => 'Visualizar Pedido',                 'group_name' => 'Pedidos',    'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 18, 'slug' => 'orders.update',          'name' => 'Editar Pedido',                     'group_name' => 'Pedidos',    'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 19, 'slug' => 'orders.conclude',        'name' => 'Concluir Pedido',                   'group_name' => 'Pedidos',    'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 20, 'slug' => 'clients.categories',     'name' => 'Categorias de Clientes (Botão)',    'group_name' => 'Clientes',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 21, 'slug' => 'categories.create',      'name' => 'Cadastrar Categoria',               'group_name' => 'Categorias', 'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 22, 'slug' => 'categories.update',      'name' => 'Editar Categoria',                  'group_name' => 'Categorias', 'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 23, 'slug' => 'categories.delete',      'name' => 'Excluir Categoria',                 'group_name' => 'Categorias', 'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
            ['id' => 24, 'slug' => 'clients.delete',         'name' => 'Excluir Cliente',                   'group_name' => 'Clientes',   'created_at' => '2025-09-05 18:20:15', 'updated_at' => '2025-09-05 18:20:15'],
        ]);
        DB::statement("UPDATE sqlite_sequence SET seq = (SELECT MAX(id) FROM permission_items) WHERE name = 'permission_items'");
    }
}
