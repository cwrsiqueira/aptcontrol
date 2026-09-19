<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

// Representa uma entrega fracionada do produto.
class OrderProductDeliveryPlan extends Model
{
    protected $fillable = [
        'order_product_id', // Produto do pedido ao qual a entrega pertence.
        'sequence',         // Ordem da entrega dentro do planejamento.
        'quantity',         // Quantidade de produtos prevista nesta entrega.
        'delivery_date',    // Data prevista para realizar a entrega.
        'carga',            // Capacidades e quantidades dos paletes da entrega.
    ];

    protected $casts = [
        'sequence' => 'integer',   // Trata a sequência como número inteiro.
        'quantity' => 'decimal:3', // Mantém a quantidade com até três casas decimais.
        'delivery_date' => 'date', // Converte a data para o formato de data do Laravel.
        'carga' => 'array',        // Converte o JSON dos paletes para array.
    ];

    public function orderProduct()
    {
        return $this->belongsTo(Order_product::class, 'order_product_id');
    }

    public function loadItems()
    {
        return $this->hasMany(LoadItem::class, 'delivery_plan_id');
    }

    // Soma os paletes definidos para a entrega.
    public function getTotalPaletesAttribute(): int
    {
        return collect($this->carga ?? [])->sum(function ($quantity) {
            return max(0, (int) $quantity);
        });
    }
}
