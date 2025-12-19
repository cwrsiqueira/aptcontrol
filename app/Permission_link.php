<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission_link extends Model
{
    protected $table = 'permission_links';

    protected $fillable = [
        'id_user',
        'slug_permission_item',
    ];

    protected $casts = [
        'id_user' => 'integer',
        'slug_permission_item' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function permissionItem()
    {
        return $this->belongsTo(Permission_item::class, 'slug_permission_item', 'slug');
    }
}
