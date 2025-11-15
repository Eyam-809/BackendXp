<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['nombre'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
