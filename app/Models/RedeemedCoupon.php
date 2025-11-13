<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RedeemedCoupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coupon_type',
        'coupon_name',
        'description',
        'points_spent',
        'coupon_code',
        'is_used',
        'used_at',
        'discount_amount',
        'discount_percentage'
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
        'discount_amount' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generateCouponCode()
    {
        return 'XP' . strtoupper(substr(md5(uniqid()), 0, 8));
    }
}
