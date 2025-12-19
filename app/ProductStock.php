<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $table = 'product_stocks';

    protected $fillable = [
        'product_id',
        'stock',
        'stock_date',
        'notes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'stock' => 'integer',
        'stock_date' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
