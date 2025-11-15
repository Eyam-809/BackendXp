<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name','description','price','stock',
        'id_user','categoria_id','subcategoria_id','tipo',
        'image','video','status_id'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }

    public function categoria()
    {
         return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

}
