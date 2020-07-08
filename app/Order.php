<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * Get the products for the order.
     */
    public function products()
    {
        return $this->hasMany('Product', 'product_id', 'id');
    }
}
