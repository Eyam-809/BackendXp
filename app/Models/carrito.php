<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class carrito extends Model
{
    use HasFactory;
    protected $table = 'carritos';

    protected $fillable = ['id', 'usuario_id', 'producto_id', 'cantidad'];

    // Relación con el modelo Product
    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
    

}
