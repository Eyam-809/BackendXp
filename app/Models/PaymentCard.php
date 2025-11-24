<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentCard extends Model
{
    protected $table = 'payment_cards';

    protected $fillable = [
        'user_id',
        'type',
        'card_holder_name',
        'card_last4',
        'card_expiry',
        'card_number',
        'cvv',
        'provider',
    ];

    protected $casts = [
        // usar el cast encrypted para proteger los datos sensibles
        'card_number' => 'encrypted',
        'cvv' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}