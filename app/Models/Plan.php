<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'planes';
    use HasFactory;

    public function usuarios()
    {
        return $this->hasMany(User::class);
    }
}



