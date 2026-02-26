<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZoneBairro extends Model
{
    protected $table = 'zone_bairros';

    protected $fillable = [
        'zone_id',
        'bairro_nome',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
