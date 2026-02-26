<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = [
        'nome',
        'obs',
    ];

    public function bairros()
    {
        return $this->hasMany(ZoneBairro::class, 'zone_id');
    }

    public function getBairrosNomesAttribute(): array
    {
        return $this->bairros->pluck('bairro_nome')->toArray();
    }
}
