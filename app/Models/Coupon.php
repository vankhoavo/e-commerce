<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'type', 'value', 'min_order_amount', 'max_discount_amount', 'usage_limit',
        'used_count', 'starts_at', 'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer', 'min_order_amount' => 'integer', 'max_discount_amount' => 'integer',
            'usage_limit' => 'integer', 'used_count' => 'integer', 'starts_at' => 'datetime',
            'ends_at' => 'datetime', 'is_active' => 'boolean',
        ];
    }
}
