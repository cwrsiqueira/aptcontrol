<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission_link extends Model
{
    protected $table = 'permission_links'; // nome correto da tabela

    protected $fillable = [
        'id_user',
        'id_permission_item',
    ];

    protected $casts = [
        'id_user'            => 'integer',
        'id_permission_item' => 'integer',
    ];
}
