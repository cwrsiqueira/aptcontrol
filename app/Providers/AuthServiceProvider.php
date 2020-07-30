<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\Response;
use App\Permission_item;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // PERMISSÕES MENU PRODUTOS
        Gate::define('menu-produtos', function($user){
            return $this->getPermissions($user, 'menu-produtos');
        });

        Gate::allows('menu-produtos-add', function($user){
            return $this->getPermissions($user, 'menu-produtos-add');
        });

        Gate::allows('menu-produtos-edit', function($user){
            return $this->getPermissions($user, 'menu-produtos-edit') ? Response::allow() : Responde::deny('Solicite Autorização');
        });

        Gate::allows('menu-produtos-estoque', function($user){
            return $this->getPermissions($user, 'menu-produtos-estoque');
        });

        Gate::allows('menu-produtos-cc', function($user){
            return $this->getPermissions($user, 'menu-produtos-cc');
        });

        Gate::allows('menu-produtos-delete', function($user){
            return $this->getPermissions($user, 'menu-produtos-delete');
        });

        // PERMISSÕES MENU CLIENTES
        Gate::define('menu-clientes', function($user){
            return $this->getPermissions($user, 'menu-clientes');
        });

        Gate::allows('menu-clientes-add', function($user){
            return $this->getPermissions($user, 'menu-clientes-add');
        });

        Gate::allows('menu-clientes-edit', function($user){
            return $this->getPermissions($user, 'menu-clientes-edit');
        });

        Gate::allows('menu-clientes-pedido', function($user){
            return $this->getPermissions($user, 'menu-clientes-pedido');
        });

        Gate::allows('menu-clientes-cc', function($user){
            return $this->getPermissions($user, 'menu-clientes-cc');
        });

        // PERMISSÕES MENU PEDIDOS
        Gate::define('menu-pedidos', function($user){
            return $this->getPermissions($user, 'menu-pedidos');
        });

        Gate::allows('menu-pedidos-concluidos', function($user){
            return $this->getPermissions($user, 'menu-pedidos-concluidos');
        });

        Gate::allows('menu-pedidos-visualizar', function($user){
            return $this->getPermissions($user, 'menu-pedidos-visualizar');
        });

        Gate::allows('menu-pedidos-edit', function($user){
            return $this->getPermissions($user, 'menu-pedidos-edit');
        });

        Gate::allows('menu-pedidos-concluir', function($user){
            return $this->getPermissions($user, 'menu-pedidos-concluir');
        });

        // PERMISSÕES MENU RELATORIOS
        Gate::define('menu-relatorios', function($user){
            return $this->getPermissions($user, 'menu-relatorios');
        });

        // PERMISSÕES MENU INTEGRACOES
        Gate::define('menu-integracoes', function($user){
            return $this->getPermissions($user, 'menu-integracoes');
        });

        // PERMISSÕES ADMINISTRADOR
        Gate::define('admin', function($user){
            return $this->getPermissions($user, 'admin');
        });
    }

    private function getPermissions($user, $menu) {
        
        $permissions = [];
        $id_permissions = $user->permissions;
        foreach ($id_permissions as $item ) {
            $permissions[] = $item['id_permission_item'];
        }
        $id_this_permission = Permission_item::where('slug', $menu)->first('id');
        if (in_array($id_this_permission['id'], $permissions) || $user->confirmed_user === 1) {
            return true;
        } 
        return false;
    }
}
