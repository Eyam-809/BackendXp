<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Direccion extends Model
{
    protected $table = 'direcciones';

    protected $fillable = [
        'user_id',
        'tipo',
        'nombre_direccion',
        'calle',
        'numero',
        'apartamento_oficina',
        'ciudad',
        'estado',
        'codigo_postal',
        'pais',
        'telefono',
        'instrucciones'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
