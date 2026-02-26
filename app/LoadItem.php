<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoadItem extends Model
{
    protected $table = 'load_items';

    protected $fillable = [
        'load_id',
        'order_product_id',
        'qtd_paletes',
        'zone_id',
        'zona_nome',
        'bairro',
    ];

    protected $casts = [
        'qtd_paletes' => 'integer',
    ];

    public function carga()
    {
        return $this->belongsTo(Load::class, 'load_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(Order_product::class, 'order_product_id');
    }

    public function getZonaExibicaoAttribute(): string
    {
        return $this->zone_id ? ($this->zone->nome ?? '') : ($this->zona_nome ?? 'SEM ZONA');
    }
}
