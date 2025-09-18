<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DeliveryReservation extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'delivery_date',
        'quant',
        'user_id',
        'expires_at',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'expires_at'    => 'datetime',
    ];
}
