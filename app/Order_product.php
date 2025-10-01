<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    // App\OrderProduct.php (Model)
    public function scopeWithSaldo($query)
    {
        return $query->addSelect([
            '*',
            DB::raw("
            SUM(quant) OVER (
                PARTITION BY order_id, product_id
                ORDER BY quant, delivery_date, id
                ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
            ) as saldo
        ")
        ]);
    }
}
