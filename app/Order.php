<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Order_product as OrderProduct;
use App\Seller;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'client_id',
        'order_date',
        'order_number',
        'withdraw',
        'complete_order',
        'seller_id',
    ];

    protected $casts = [
        'order_date'     => 'date',
        'order_total'    => 'decimal:2', // ajuste conforme seu tipo/escala
        'complete_order' => 'integer',
        'favorite_date' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'order_number');
    }

    // Alias opcional
    public function items(): HasMany
    {
        return $this->orderProducts();
    }

    public function products(): HasManyThrough
    {
        return $this->hasManyThrough(
            Product::class,        // modelo de destino
            OrderProduct::class,   // modelo intermediário
            'order_id',            // FK no intermediário que referencia orders
            'id',                  // FK (PK) no destino (products)
            'order_number',        // chave local em orders
            'product_id'           // chave local no intermediário (order_products)
        );
    }
}
