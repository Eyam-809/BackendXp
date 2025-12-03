<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanVigencia extends Model
{
    use HasFactory;

    protected $table = 'plan_vigencia';

    // Agrego plan_id y payment_reference para permitir guardarlos
    protected $fillable = [
        'user_id',
        'plan_id',
        'fecha_inicio',
        'fecha_fin',
        'payment_reference',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
