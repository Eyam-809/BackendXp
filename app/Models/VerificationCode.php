<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number',
        'code',
        'expires_at',
        'used',
        'used_at',
        'user_type'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Scope para códigos válidos (no expirados y no usados)
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', Carbon::now())
                    ->where('used', false);
    }

    /**
     * Scope para un número de teléfono específico
     */
    public function scopeForPhone($query, $phoneNumber)
    {
        return $query->where('phone_number', $phoneNumber);
    }

    /**
     * Marca el código como usado
     */
    public function markAsUsed()
    {
        $this->update([
            'used' => true,
            'used_at' => Carbon::now()
        ]);
    }

    /**
     * Verifica si el código ha expirado
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Verifica si el código es válido
     */
    public function isValid()
    {
        return !$this->isExpired() && !$this->used;
    }
}
