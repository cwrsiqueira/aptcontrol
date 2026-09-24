<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseQuote extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'supplier_name',
        'amount',
        'quote_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quote_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
