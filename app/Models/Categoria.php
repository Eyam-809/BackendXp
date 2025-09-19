<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    // Nombre de la tabla (opcional si sigue la convención plural)
    protected $table = 'categorias';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
    ];

    /**
     * Relación: Una categoría tiene muchos productos.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'categoria_id');
    }
}

