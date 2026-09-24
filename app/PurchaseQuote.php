<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// Representa um orçamento vinculado ao pedido de compra.
class PurchaseQuote extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'supplier_name',
        'amount',
        'quote_date',
        'notes',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'attachment_size',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quote_date' => 'date',
        'attachment_size' => 'integer',
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
