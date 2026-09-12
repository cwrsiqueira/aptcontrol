<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderProductDeliveryPlan extends Model
{
    protected $fillable = [
        'order_product_id',
        'sequence',
        'quantity',
        'delivery_date',
        'carga',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'quantity' => 'decimal:3',
        'delivery_date' => 'date',
        'carga' => 'array',
    ];

    public function orderProduct()
    {
        return $this->belongsTo(Order_product::class, 'order_product_id');
    }

    public function loadItems()
    {
        return $this->hasMany(LoadItem::class, 'delivery_plan_id');
    }

    public function getTotalPaletesAttribute(): int
    {
        return collect($this->carga ?? [])->sum(function ($quantity) {
            return max(0, (int) $quantity);
        });
    }
}
