<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'compra_id',
        'provider',
        'transaction_id',
        'status',
        'payment_method',
        'amount',
        'currency',
        'card_last4',
        'card_brand',
        'receipt_url',
        'raw_request',
        'raw_response'
    ];

    public function compra()
{
    return $this->belongsTo(Compra::class, 'compra_id');
}

}

