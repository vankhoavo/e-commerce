<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'code', 'status', 'customer_name', 'customer_phone', 'customer_email',
        'customer_address', 'note', 'payment', 'paypal_order_id', 'subtotal', 'shipping',
        'total_shipping', 'total', 'cancelled_at', 'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
            'returned_at' => 'datetime',
            'subtotal' => 'integer',
            'shipping' => 'integer',
            'total_shipping' => 'integer',
            'total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
