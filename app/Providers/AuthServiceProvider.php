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
        Gate::define('menu-categorias', function ($user) {
            return $this->getPermissions($user, 'menu-categorias');
        });

        // PERMISSÕES MENU PRODUTOS
        Gate::define('menu-produtos', function ($user) {
            return $this->getPermissions($user, 'menu-produtos');
        });

        // PERMISSÕES MENU CLIENTES
        Gate::define('menu-clientes', function ($user) {
            return $this->getPermissions($user, 'menu-clientes');
        });

        // PERMISSÕES MENU PEDIDOS
        Gate::define('menu-pedidos', function ($user) {
            return $this->getPermissions($user, 'menu-pedidos');
        });

        // PERMISSÕES MENU RELATORIOS
        Gate::define('menu-relatorios', function ($user) {
            return $this->getPermissions($user, 'menu-relatorios');
        });

        // PERMISSÕES MENU INTEGRACOES
        Gate::define('menu-integracoes', function ($user) {
            return $this->getPermissions($user, 'menu-integracoes');
        });

        // PERMISSÕES ADMINISTRADOR
        Gate::define('admin', function ($user) {
            return $this->getPermissions($user, 'admin');
        });
    }

    private function getPermissions($user, string $menu): bool
    {
        // Admin bypass
        if ($user->is_admin) {
            return true;
        }

        // ID da permissão (pelo slug); retorna null se não existir
        $permissionId = Permission_item::where('slug', $menu)->value('id');
        if (!$permissionId) {
            return false;
        }

        // IDs de permissões do usuário (coleção de Permission_link)
        $userPermissionIds = $user->permissions
            ->pluck('id_permission_item')
            ->map('intval')
            ->all();

        // Comparação estrita e tipada
        return in_array((int) $permissionId, $userPermissionIds, true);
    }
}
