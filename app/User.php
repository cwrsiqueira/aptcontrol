<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * Campos liberados para atribuição em massa.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'confirmed_user', // <- inclui para permitir seed/updates
    ];

    /**
     * Campos ocultos em arrays/JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts nativos.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'confirmed_user'    => 'integer', // <- garante tipo int (evita "1" vs 1)
    ];

    /**
     * Relação com os vínculos de permissão.
     */
    public function permissions()
    {
        return $this->hasMany('App\Permission_link', 'id_user', 'id');
    }

    /**
     * Atributo dinâmico: $user->is_admin
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->confirmed_user === 1;
    }
}
