<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanVigencia extends Model
{
    use HasFactory;

    protected $table = 'plan_vigencia';

    protected $fillable = [
        'user_id',
        'fecha_inicio',
        'fecha_fin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
