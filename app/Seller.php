<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $table = 'sellers';

    protected $fillable = [
        'name',
        'contact_type',
        'contact_value',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }
}
