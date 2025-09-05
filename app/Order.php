<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['client_id', 'order_date', 'order_number', 'order_total', 'payment', 'withdraw', 'complete_order'];
    protected $casts = ['order_date' => 'date'];

    /**
     * Get the products for the order.
     */
    public function products()
    {
        return $this->hasMany('Product', 'product_id', 'id');
    }
}
