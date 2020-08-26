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

        // PERMISSÕES MENU CATEGORIAS DE CLIENTES
        Gate::define('menu-categorias', function($user){
            return $this->getPermissions($user, 'menu-categorias');
        });

        // PERMISSÕES MENU PRODUTOS
        Gate::define('menu-produtos', function($user){
            return $this->getPermissions($user, 'menu-produtos');
        });

        // PERMISSÕES MENU CLIENTES
        Gate::define('menu-clientes', function($user){
            return $this->getPermissions($user, 'menu-clientes');
        });

        // PERMISSÕES MENU PEDIDOS
        Gate::define('menu-pedidos', function($user){
            return $this->getPermissions($user, 'menu-pedidos');
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
