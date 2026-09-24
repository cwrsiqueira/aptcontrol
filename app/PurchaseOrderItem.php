<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// Representa um item solicitado no pedido de compra.
class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'description',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
