<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    protected $fillable = [
        'responsavel',
        'capacidade_paletes',
        'modelo',
        'placa',
        'obs',
    ];

    protected $casts = [
        'capacidade_paletes' => 'integer',
    ];
}
