<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Load extends Model
{
    protected $fillable = [
        'truck_id',
        'motorista',
        'status',
        'data_montagem',
        'obs',
    ];

    protected $casts = [
        'data_montagem' => 'datetime',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function items()
    {
        return $this->hasMany(LoadItem::class, 'load_id');
    }

    public function getTotalPaletesAttribute(): int
    {
        return $this->items->sum('qtd_paletes');
    }
}
