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

    /**
     * Itens da ordem (tabela pivot/itens): orders -> order_products
     * FK em order_products: order_id
     * Chave local em orders: order_number
     */
    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'order_number');
    }

    /**
     * Alias opcional para quem preferir 'items'
     */
    public function items(): HasMany
    {
        return $this->orderProducts();
    }

    /**
     * Produtos da ordem (via hasManyThrough): orders -> order_products -> products
     * order_products.order_id    -> orders.order_number
     * order_products.product_id  -> products.id
     */
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

    /**
     * Recalcula e persiste o total da ordem somando order_products.total_price.
     * Se você armazena em centavos, ajuste a conversão aqui.
     */
    public function recalcTotal(): self
    {
        // Somatório bruto
        $sum = $this->orderProducts()->sum('total_price');

        // Caso seu total_price esteja em centavos, descomente a próxima linha:
        // $sum = $sum / 100;

        $this->order_total = $sum;
        $this->save();

        return $this;
    }
}
