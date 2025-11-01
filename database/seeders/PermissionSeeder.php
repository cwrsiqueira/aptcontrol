<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        DB::table('permission_items')->insert([
            // MENUS
            ['slug' => 'menu-produtos',             'name' => 'Menu Produtos',             'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu-clientes',             'name' => 'Menu Clientes',             'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu-categorias',           'name' => 'Menu Categorias',           'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu-vendedores',           'name' => 'Menu Vendedores',           'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu-pedidos',              'name' => 'Menu Pedidos',              'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu-produtos-pedidos',     'name' => 'Menu Produtos Pedidos',     'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'menu-relatorios',           'name' => 'Menu Relatórios',           'group_name' => 'Menus',              'created_at' => $now, 'updated_at' => $now],

            // PRODUTOS 
            ['slug' => 'products.create',           'name' => 'Cadastrar Produto',         'group_name' => 'Produtos',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'products.view',             'name' => 'Visualizar Produto',        'group_name' => 'Produtos',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'products.update',           'name' => 'Editar Produto',            'group_name' => 'Produtos',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'products.delete',           'name' => 'Excluir Produto',           'group_name' => 'Produtos',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'products.cc',               'name' => 'Ver entregas (Produtos)',   'group_name' => 'Produtos',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'products.marcar_produto',   'name' => 'Marcar produtos',           'group_name' => 'Produtos',           'created_at' => $now, 'updated_at' => $now],

            // CLIENTES 
            ['slug' => 'clients.create',            'name' => 'Cadastrar Cliente',         'group_name' => 'Clientes',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'clients.view',              'name' => 'Visualizar Cliente',        'group_name' => 'Clientes',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'clients.update',            'name' => 'Editar Cliente',            'group_name' => 'Clientes',           'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'clients.delete',            'name' => 'Excluir Cliente',           'group_name' => 'Clientes',           'created_at' => $now, 'updated_at' => $now],

            // CATEGORIAS DE CLIENTES 
            ['slug' => 'categories.create',         'name' => 'Cadastrar Categoria',       'group_name' => 'Categorias',         'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'categories.view',           'name' => 'Visualizar Categoria',      'group_name' => 'Categorias',         'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'categories.update',         'name' => 'Editar Categoria',          'group_name' => 'Categorias',         'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'categories.delete',         'name' => 'Excluir Categoria',         'group_name' => 'Categorias',         'created_at' => $now, 'updated_at' => $now],

            // VENDEDORES 
            ['slug' => 'sellers.create',            'name' => 'Cadastrar Vendedor',        'group_name' => 'Vendedores',         'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sellers.view',              'name' => 'Visualizar Vendedor',       'group_name' => 'Vendedores',         'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sellers.update',            'name' => 'Editar Vendedor',           'group_name' => 'Vendedores',         'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'sellers.delete',            'name' => 'Excluir Vendedor',          'group_name' => 'Vendedores',         'created_at' => $now, 'updated_at' => $now],

            // PEDIDOS
            ['slug' => 'orders.create',             'name' => 'Efetuar Pedido',            'group_name' => 'Pedidos',            'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'orders.view',               'name' => 'Visualizar Pedido',         'group_name' => 'Pedidos',            'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'orders.update',             'name' => 'Editar Pedido',             'group_name' => 'Pedidos',            'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'orders.delete',             'name' => 'Excluir Pedido',            'group_name' => 'Pedidos',            'created_at' => $now, 'updated_at' => $now],

            // PRODUTOS PEDIDOS
            ['slug' => 'order_products.create',     'name' => 'Cadastrar Produto Pedido',  'group_name' => 'Produtos Pedidos',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'order_products.view',       'name' => 'Visualizar Produto Pedido', 'group_name' => 'Produtos Pedidos',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'order_products.update',     'name' => 'Editar Produto Pedido',     'group_name' => 'Produtos Pedidos',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'order_products.delete',     'name' => 'Excluir Produto Pedido',    'group_name' => 'Produtos Pedidos',   'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'order_products.delivery',   'name' => 'Entregar Produto Pedido',   'group_name' => 'Produtos Pedidos',   'created_at' => $now, 'updated_at' => $now],
        ]);

        // sem vincular ao admin; Auth::user()->is_admin já libera tudo
    }
}
