<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    protected $table = 'pedidos';

    protected $fillable = [
        'user_id',
        'compra_id',
        'estado',
        'total',
        'fecha_pedido',
        'fecha_actualizacion_estado',
        'direccion_envio',
        'telefono_contacto',
        'producto_id',
    ];

    protected $dates = [
        'fecha_pedido',
        'fecha_actualizacion_estado',
        'created_at',
        'updated_at',
    ];

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con la compra
    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    

public function producto()
{
    return $this->hasOneThrough(
        Product::class,
        DetalleCompra::class,
        'compra_id', // FK en DetalleCompra
        'id',        // PK en Product
        'compra_id', // FK en Pedido
        'producto_id' // PK en DetalleCompra
    );
}


    // Relación opcional con productos si lo necesitas
    public function productos()
    {
        return $this->belongsToMany(Product::class, 'detalle_pedidos', 'pedido_id', 'producto_id')
                    ->withPivot('cantidad', 'precio_unitario', 'subtotal');
    }
}
