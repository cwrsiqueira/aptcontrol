<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $casts = [
        'is_favorite' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Clients_category::class, 'id_categoria', 'id');
    }
}
