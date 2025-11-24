<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
     use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'metodo_pago',
        'estado',
        'direccion_envio',
        'telefono_contacto',
        'fecha_pago',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function producto() {
    return $this->belongsTo(Product::class);
}

public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction:: class, 'compra_id');
    }

}
