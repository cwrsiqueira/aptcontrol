<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission_item extends Model
{
    protected $table = 'permission_items';

    protected $fillable = [
        'slug',
        'name',
        'group_name',
    ];

    public function links()
    {
        // permission_links.slug_permission_item -> permission_items.slug
        return $this->hasMany(Permission_link::class, 'slug_permission_item', 'slug');
    }
}
