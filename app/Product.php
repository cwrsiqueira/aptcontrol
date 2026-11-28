<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'current_stock',
        'daily_production_forecast',
        'img_url',
    ];

    public function stocks()
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'id');
    }
}

