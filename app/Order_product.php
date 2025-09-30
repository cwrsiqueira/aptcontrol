<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order_product extends Model
{
    protected $fillable = [
        "order_id",
        "product_id",
        "quant",
        "unit_price",
        "total_price",
        "delivery_date",
        "favorite_delivery",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_number');
    }
}
