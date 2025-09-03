<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $now = now();

        DB::table('permission_items')->insert([
            // MENUS (1..5)
            ['id'=>1, 'slug'=>'menu-produtos',    'name'=>'Menu Produtos',    'group_name'=>'Menus', 'created_at'=>$now,'updated_at'=>$now],
            ['id'=>2, 'slug'=>'menu-clientes',    'name'=>'Menu Clientes',    'group_name'=>'Menus', 'created_at'=>$now,'updated_at'=>$now],
            ['id'=>3, 'slug'=>'menu-pedidos',     'name'=>'Menu Pedidos',     'group_name'=>'Menus', 'created_at'=>$now,'updated_at'=>$now],
            ['id'=>4, 'slug'=>'menu-relatorios',  'name'=>'Menu Relatórios',  'group_name'=>'Menus', 'created_at'=>$now,'updated_at'=>$now],
            ['id'=>5, 'slug'=>'menu-integracoes', 'name'=>'Menu Integrações', 'group_name'=>'Menus', 'created_at'=>$now,'updated_at'=>$now],

            // PRODUTOS (7..11)
            ['id'=>7,  'slug'=>'products.create', 'name'=>'Cadastrar Produto',           'group_name'=>'Produtos',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>8,  'slug'=>'products.update', 'name'=>'Editar Produto',              'group_name'=>'Produtos',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>9,  'slug'=>'products.stock',  'name'=>'+ Estoque (Produto)',         'group_name'=>'Produtos',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>10, 'slug'=>'products.cc',     'name'=>'Conta Corrente (Produto)',    'group_name'=>'Produtos',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>11, 'slug'=>'products.delete', 'name'=>'Excluir Produto',             'group_name'=>'Produtos',  'created_at'=>$now,'updated_at'=>$now],

            // CLIENTES (12,13,15,20,24)
            ['id'=>12, 'slug'=>'clients.create',  'name'=>'Cadastrar Cliente',           'group_name'=>'Clientes',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>13, 'slug'=>'clients.update',  'name'=>'Editar Cliente',              'group_name'=>'Clientes',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>15, 'slug'=>'clients.cc',      'name'=>'Conta Corrente (Cliente)',    'group_name'=>'Clientes',  'created_at'=>$now,'updated_at'=>$now],
            ['id'=>20, 'slug'=>'clients.categories','name'=>'Categorias de Clientes (Botão)','group_name'=>'Clientes','created_at'=>$now,'updated_at'=>$now],
            ['id'=>24, 'slug'=>'clients.delete',  'name'=>'Excluir Cliente',             'group_name'=>'Clientes',  'created_at'=>$now,'updated_at'=>$now],

            // CATEGORIAS DE CLIENTES (21..23)
            ['id'=>21, 'slug'=>'categories.create','name'=>'Cadastrar Categoria',        'group_name'=>'Categorias','created_at'=>$now,'updated_at'=>$now],
            ['id'=>22, 'slug'=>'categories.update','name'=>'Editar Categoria',           'group_name'=>'Categorias','created_at'=>$now,'updated_at'=>$now],
            ['id'=>23, 'slug'=>'categories.delete','name'=>'Excluir Categoria',          'group_name'=>'Categorias','created_at'=>$now,'updated_at'=>$now],

            // PEDIDOS (14,16..19)
            ['id'=>14, 'slug'=>'orders.create',   'name'=>'Efetuar Pedido',              'group_name'=>'Pedidos',   'created_at'=>$now,'updated_at'=>$now],
            ['id'=>16, 'slug'=>'orders.view_completed','name'=>'Mostrar Pedidos Concluídos','group_name'=>'Pedidos','created_at'=>$now,'updated_at'=>$now],
            ['id'=>17, 'slug'=>'orders.view',     'name'=>'Visualizar Pedido',           'group_name'=>'Pedidos',   'created_at'=>$now,'updated_at'=>$now],
            ['id'=>18, 'slug'=>'orders.update',   'name'=>'Editar Pedido',               'group_name'=>'Pedidos',   'created_at'=>$now,'updated_at'=>$now],
            ['id'=>19, 'slug'=>'orders.conclude', 'name'=>'Concluir Pedido',             'group_name'=>'Pedidos',   'created_at'=>$now,'updated_at'=>$now],
        ]);

        // sem vincular ao admin; Auth::user()->is_admin já libera tudo
    }
}
